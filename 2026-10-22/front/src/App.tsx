import { useState } from 'react'
import './App.css'

type FormData = { name: string; email: string; subject: string; message: string; website: string }
type Result = { ok: boolean; message: string; reference?: string }
const empty: FormData = { name: '', email: '', subject: '', message: '', website: '' }

function App() {
  const [form, setForm] = useState<FormData>(empty)
  const [step, setStep] = useState<'form' | 'confirm' | 'done'>('form')
  const [csrf, setCsrf] = useState('')
  const [result, setResult] = useState<Result | null>(null)
  const [busy, setBusy] = useState(false)
  const [errors, setErrors] = useState<Record<string, string>>({})

  async function startConfirm(event: React.FormEvent) {
    event.preventDefault()
    const next: Record<string, string> = {}
    if (!form.name.trim()) next.name = 'お名前を入力してください。'
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) next.email = '有効なメールアドレスを入力してください。'
    if (!form.subject.trim()) next.subject = '件名を入力してください。'
    if (!form.message.trim()) next.message = 'お問い合わせ内容を入力してください。'
    setErrors(next)
    if (Object.keys(next).length) return
    try {
      const response = await fetch('./api/submit.php?action=token', { credentials: 'same-origin' })
      const data = await response.json()
      if (!response.ok) throw new Error(data.message || 'フォームを準備できませんでした。')
      setCsrf(data.csrf)
      setResult(null)
      setStep('confirm')
    } catch (error) {
      setResult({ ok: false, message: error instanceof Error ? error.message : '通信に失敗しました。' })
    }
  }

  async function submit() {
    setBusy(true); setResult(null)
    try {
      const response = await fetch('./api/submit.php', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf }, body: JSON.stringify({ ...form, csrf }) })
      const data = await response.json()
      setResult({ ok: response.ok && data.ok, message: data.message || '送信に失敗しました。', reference: data.reference })
      if (response.ok && data.ok) setStep('done')
    } catch {
      setResult({ ok: false, message: '通信に失敗しました。入力内容を控えたうえで、時間をおいて再度お試しください。' })
    } finally { setBusy(false) }
  }

  function update(key: keyof FormData, value: string) { setForm(current => ({ ...current, [key]: value })); setErrors(current => ({ ...current, [key]: '' })) }
  function restart() { setForm(empty); setErrors({}); setResult(null); setStep('form') }

  return <div className="site-shell">
    <header className="topbar"><a className="brand" href="#top"><span className="brand-mark">i.</span><span>Inquiry<span className="brand-light"> desk</span></span></a><a className="header-link" href="#contact">お問い合わせ <span aria-hidden="true">↗</span></a></header>
    <main id="top">
      <section className="hero"><div className="hero-copy"><span className="eyebrow"><span className="eyebrow-line" /> CONTACT US</span><h1>お話を聞かせて<br /><em>ください。</em></h1><p className="hero-text">ご質問やご相談など、こちらのフォームからお気軽にお送りください。<br className="desktop-only" />内容を確認のうえ、担当者よりご連絡いたします。</p><div className="response-note"><span className="note-icon">✳</span><span>通常 <strong>2〜3営業日</strong> 以内にお返事しています。</span></div><div className="hero-decoration" aria-hidden="true"><span>i</span><span>.</span></div></div>
        <div className="hero-side"><div className="side-orbit orbit-one"/><div className="side-orbit orbit-two"/><div className="side-copy"><span>HERE TO HELP</span><strong>あなたの声を、<br />お聞かせください。</strong><small>Questions, ideas, or just a hello.</small></div><div className="side-index"><span>01 — 04</span><span>✳</span></div></div></section>
      <section className="contact-section" id="contact"><div className="section-intro"><span className="eyebrow"><span className="eyebrow-line"/> GET IN TOUCH</span><h2>{step === 'done' ? '送信を受け付けました。' : step === 'confirm' ? '内容をご確認ください。' : 'お問い合わせフォーム'}</h2><p>{step === 'done' ? 'お問い合わせありがとうございます。担当者よりご連絡いたします。' : step === 'confirm' ? '入力内容にお間違いがないかご確認ください。' : '必要事項をご入力ください。'}</p></div>
        {result && !result.ok && <div className="alert error" role="alert">{result.message}</div>}
        {step === 'done' ? <div className="success-card"><span className="success-check">✓</span><h3>お問い合わせありがとうございます</h3><p>内容を確認し、担当者よりご連絡いたします。</p>{result?.reference && <p className="reference">受付番号：{result.reference}</p>}<button className="text-button" onClick={restart}>新しいお問い合わせを送る <span>→</span></button></div> : step === 'confirm' ? <div className="form-card confirm-card">{([['name','お名前'],['email','メールアドレス'],['subject','件名'],['message','お問い合わせ内容']] as const).map(([key,label]) => <div className="confirm-row" key={key}><span>{label}</span><p>{form[key] || '—'}</p></div>)}<div className="form-actions"><button className="button button-muted" onClick={() => { setResult(null); setStep('form') }}>← 入力画面に戻る</button><button className="button button-primary" onClick={submit} disabled={busy}>{busy ? '送信中…' : 'この内容で送信する'} <span>→</span></button></div></div> : <form className="form-card" onSubmit={startConfirm} noValidate>
          <div className="form-grid"><Field label="お名前" required id="name" error={errors.name}><input id="name" autoComplete="name" maxLength={100} value={form.name} onChange={e => update('name', e.target.value)} placeholder="例）山田 花子" aria-invalid={!!errors.name}/></Field><Field label="メールアドレス" required id="email" error={errors.email}><input id="email" type="email" autoComplete="email" maxLength={254} value={form.email} onChange={e => update('email', e.target.value)} placeholder="name@example.com" aria-invalid={!!errors.email}/></Field></div>
          <Field label="件名" required id="subject" error={errors.subject}><input id="subject" maxLength={200} value={form.subject} onChange={e => update('subject', e.target.value)} placeholder="お問い合わせの件名" aria-invalid={!!errors.subject}/></Field>
          <Field label="お問い合わせ内容" required id="message" error={errors.message}><textarea id="message" rows={6} maxLength={10000} value={form.message} onChange={e => update('message', e.target.value)} placeholder="お問い合わせ内容をご記入ください" aria-invalid={!!errors.message}/><span className="field-hint">{form.message.length} / 10,000</span></Field>
          <div className="website-field" aria-hidden="true"><label htmlFor="website">Website</label><input id="website" tabIndex={-1} autoComplete="off" value={form.website} onChange={e => update('website',e.target.value)}/></div>
          <div className="privacy-note"><span>◎</span><p>ご入力いただいた個人情報は、お問い合わせへの回答のみに使用します。</p></div>
          <div className="form-footer"><span><b>*</b> は必須項目です</span><button className="button button-primary" type="submit">入力内容を確認する <span>→</span></button></div>
        </form>}
      </section>
      <section className="bottom-note"><span>✳</span><p>フォームの利用でお困りですか？<br /><small>入力内容を確認してから送信できます。</small></p><span className="bottom-arrow">↗</span></section>
    </main><footer><span>© {new Date().getFullYear()} Inquiry desk</span><span>Thoughtful conversations start here. <b>✳</b></span></footer>
  </div>
}
function Field({ label, required, id, error, children }: { label: string; required?: boolean; id: string; error?: string; children: React.ReactNode }) { return <div className="field"><label htmlFor={id}>{label}{required && <span className="required">必須</span>}</label>{children}{error && <span className="field-error" role="alert">{error}</span>}</div> }
export default App
