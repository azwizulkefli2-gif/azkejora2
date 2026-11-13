<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AZ Kejora | Simplified SaaS Solution</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/fontsource/fonts/inter@latest/latin-400-normal.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/fontsource/fonts/inter@latest/latin-600-normal.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/fontsource/fonts/inter@latest/latin-700-normal.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/fontsource/fonts/inter@latest/latin-800-normal.css">
<style>
:root{
  --bg:#05081a;
  --bg-2:#0a0f2e;
  --bg-3:#0f1740;
  --surface:rgba(255,255,255,0.04);
  --surface-2:rgba(255,255,255,0.06);
  --surface-3:rgba(255,255,255,0.08);
  --border:rgba(255,255,255,0.08);
  --border-2:rgba(255,255,255,0.14);
  --text:#e6ecff;
  --muted:#9aa6c7;
  --faint:#6b7699;
  --brand:#3b82f6;
  --brand-2:#60a5fa;
  --cyan:#22d3ee;
  --violet:#8b5cf6;
  --emerald:#10b981;
  --rose:#f43f5e;
  --grad:linear-gradient(135deg,#3b82f6 0%,#8b5cf6 50%,#22d3ee 100%);
  --grad-2:linear-gradient(135deg,#1e3a8a,#3b82f6);
  --glow:0 0 60px rgba(59,130,246,0.35);
  --card:0 1px 0 rgba(255,255,255,0.06) inset, 0 20px 60px -20px rgba(0,0,0,0.6);
}
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{
  font-family:'Inter',system-ui,-apple-system,sans-serif;
  background:var(--bg);
  color:var(--text);
  -webkit-font-smoothing:antialiased;
  overflow-x:hidden;
  line-height:1.6;
}
a{color:inherit;text-decoration:none}
button{font:inherit;cursor:pointer;border:none;background:none;color:inherit}
img{max-width:100%;display:block}
.wrap{max-width:1200px;margin:0 auto;padding:0 24px}

/* Background effects */
.bg-grid{
  position:fixed;inset:0;z-index:0;pointer-events:none;
  background-image:
    linear-gradient(rgba(59,130,246,0.04) 1px,transparent 1px),
    linear-gradient(90deg,rgba(59,130,246,0.04) 1px,transparent 1px);
  background-size:52px 52px;
  mask-image:radial-gradient(ellipse at center,black 30%,transparent 80%);
}
.bg-orb{
  position:fixed;border-radius:50%;filter:blur(80px);pointer-events:none;z-index:0;opacity:0.6;
}
.bg-orb.o1{top:-10%;left:-10%;width:500px;height:500px;background:radial-gradient(circle,#3b82f6,transparent 70%)}
.bg-orb.o2{top:30%;right:-15%;width:600px;height:600px;background:radial-gradient(circle,#8b5cf6,transparent 70%)}
.bg-orb.o3{bottom:-20%;left:20%;width:500px;height:500px;background:radial-gradient(circle,#22d3ee,transparent 70%)}

/* NAV */
.nav{
  position:fixed;top:0;left:0;right:0;z-index:50;
  padding:16px 16px;
}
.nav-inner{
  max-width:1200px;margin:0 auto;
  display:flex;align-items:center;justify-content:space-between;gap:16px;
  background:rgba(10,15,46,0.7);
  backdrop-filter:blur(20px);
  -webkit-backdrop-filter:blur(20px);
  border:1px solid var(--border);
  border-radius:18px;
  padding:12px 20px;
  box-shadow:0 10px 40px -10px rgba(0,0,0,0.5);
}
.brand{display:flex;align-items:center;gap:10px;font-weight:800;font-size:17px;letter-spacing:-0.02em}
.logo{
  width:36px;height:36px;border-radius:10px;
  background:var(--grad);
  display:grid;place-items:center;
  box-shadow:0 8px 20px -4px rgba(59,130,246,0.6);
  flex-shrink:0;
  position:relative;overflow:hidden;
}
.logo::after{
  content:'';position:absolute;inset:0;
  background:linear-gradient(135deg,rgba(255,255,255,0.3),transparent 50%);
}
.logo svg{width:20px;height:20px;position:relative;z-index:1}
.brand em{font-style:normal;background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent}
.links{display:flex;gap:28px;font-size:14px;font-weight:600;color:var(--muted)}
.links a{transition:color .2s;position:relative}
.links a:hover{color:var(--text)}
.links a::after{
  content:'';position:absolute;bottom:-6px;left:0;right:0;height:2px;
  background:var(--grad);transform:scaleX(0);transition:transform .3s;
}
.links a:hover::after{transform:scaleX(1)}
.nav-cta{display:flex;align-items:center;gap:10px}
.menu-toggle{display:none;width:40px;height:40px;border-radius:10px;background:var(--surface-2);align-items:center;justify-content:center;color:var(--text);transition:.2s}
.menu-toggle:hover{background:var(--surface-3)}

/* Buttons */
.btn{
  display:inline-flex;align-items:center;justify-content:center;gap:8px;
  border-radius:12px;padding:11px 20px;font-size:14px;font-weight:600;
  transition:all .25s;white-space:nowrap;
}
.btn.primary{
  background:var(--grad);color:#fff;
  box-shadow:0 10px 30px -8px rgba(59,130,246,0.6);
  position:relative;overflow:hidden;
}
.btn.primary::before{
  content:'';position:absolute;inset:0;
  background:linear-gradient(135deg,rgba(255,255,255,0.2),transparent);
  opacity:0;transition:opacity .25s;
}
.btn.primary:hover{transform:translateY(-2px);box-shadow:0 14px 40px -8px rgba(59,130,246,0.8)}
.btn.primary:hover::before{opacity:1}
.btn.ghost{background:var(--surface-2);border:1px solid var(--border-2);color:var(--text)}
.btn.ghost:hover{background:var(--surface-3);border-color:rgba(59,130,246,0.4)}
.btn.full{width:100%;padding:14px}

/* HERO */
.hero{
  position:relative;padding:160px 0 100px;overflow:hidden;
  z-index:1;
}
.hero-grid{
  position:relative;display:grid;grid-template-columns:1.05fr .95fr;gap:60px;align-items:center;
}
.eyebrow{
  display:inline-flex;align-items:center;gap:10px;
  background:var(--surface-2);
  border:1px solid var(--border-2);
  color:var(--brand-2);
  border-radius:999px;padding:7px 16px;
  font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;
  backdrop-filter:blur(10px);
}
.dot{width:7px;height:7px;border-radius:50%;background:var(--emerald);box-shadow:0 0 12px var(--emerald);animation:pulse 1.6s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
h1.big{
  margin-top:24px;
  font-size:clamp(34px,5vw,62px);
  line-height:1.05;letter-spacing:-0.03em;font-weight:800;
}
.grad-text{background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent}
.lead{
  margin-top:24px;max-width:560px;
  font-size:clamp(15px,1.4vw,18px);line-height:1.7;color:var(--muted);
}
.cta-row{margin-top:32px;display:flex;flex-wrap:wrap;gap:12px}
.checks{margin-top:24px;display:flex;flex-wrap:wrap;gap:20px;font-size:14px;color:var(--muted)}
.checks span{display:inline-flex;align-items:center;gap:6px}
.checks span::before{content:'✓';color:var(--emerald);font-weight:800}

/* Hero mock dashboard */
.mock{position:relative;perspective:1500px}
.mock-card{
  position:relative;
  background:linear-gradient(180deg,rgba(15,23,64,0.9),rgba(10,15,46,0.95));
  border:1px solid var(--border-2);
  border-radius:20px;
  padding:20px;
  box-shadow:var(--card),0 0 80px -20px rgba(59,130,246,0.4);
  transform:rotateY(-8deg) rotateX(4deg);
  animation:floatMock 6s ease-in-out infinite;
}
@keyframes floatMock{0%,100%{transform:rotateY(-8deg) rotateX(4deg) translateY(0)}50%{transform:rotateY(-8deg) rotateX(4deg) translateY(-12px)}}
.mock-top{display:flex;align-items:center;gap:6px;padding-bottom:14px;border-bottom:1px solid var(--border)}
.mock-top span{width:10px;height:10px;border-radius:50%;background:var(--surface-3)}
.mock-top span:nth-child(1){background:#f43f5e}
.mock-top span:nth-child(2){background:#f59e0b}
.mock-top span:nth-child(3){background:#10b981}
.mock-top i{font-style:normal;font-size:11px;color:var(--faint);margin-left:auto}
.mock-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:16px}
.mock-kpi{
  background:var(--surface-2);border:1px solid var(--border);
  border-radius:12px;padding:14px;text-align:center;
}
.mock-kpi b{font-size:clamp(14px,1.8vw,18px);font-weight:800;display:block}
.mock-kpi p{font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--faint);margin-top:4px}
.mock-kpi.g b{color:var(--emerald)}
.mock-kpi.b b{color:var(--brand-2)}
.mock-kpi.c b{color:var(--cyan)}
.mock-bars{display:flex;align-items:flex-end;gap:6px;height:110px;margin-top:16px;padding:12px;background:var(--surface);border-radius:12px;border:1px solid var(--border)}
.mock-bars i{flex:1;background:var(--grad);border-radius:4px 4px 0 0;opacity:.9;animation:barGrow 1.2s ease-out both}
@keyframes barGrow{from{height:0 !important}}
.mock-line{margin-top:16px;height:60px;background:var(--surface);border-radius:12px;border:1px solid var(--border);position:relative;overflow:hidden}
.mock-line svg{width:100%;height:100%}

.chip{
  position:absolute;
  background:rgba(15,23,64,0.9);
  border:1px solid var(--border-2);
  border-radius:14px;
  box-shadow:0 10px 40px -10px rgba(0,0,0,0.5);
  padding:10px 14px;
  display:flex;gap:10px;align-items:center;
  backdrop-filter:blur(10px);
  animation:floaty 5s ease-in-out infinite;
}
.chip .ic{width:34px;height:34px;border-radius:10px;display:grid;place-items:center;font-size:15px}
.chip b{display:block;font-size:12px;color:var(--text)}
.chip small{color:var(--muted);font-size:11px}
@keyframes floaty{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}

/* Stats */
.stats{
  margin-top:80px;
  display:grid;grid-template-columns:repeat(4,1fr);gap:20px;
  background:var(--surface);
  border:1px solid var(--border);
  border-radius:20px;
  padding:32px;
  backdrop-filter:blur(10px);
  position:relative;z-index:1;
}
.stats b{font-size:32px;font-weight:800;display:block;background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent}
.stats p{margin-top:4px;font-size:13px;color:var(--muted)}

/* Sections */
section{padding:100px 0;position:relative;z-index:1}
.sec-head{text-align:center;max-width:680px;margin:0 auto}
h2.sec{
  margin-top:20px;
  font-size:clamp(28px,3.6vw,44px);
  font-weight:800;letter-spacing:-0.02em;line-height:1.15;
}
.sec-head p{margin-top:16px;color:var(--muted);font-size:16px;line-height:1.7}

/* Problem/Solution split */
.split{
  display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center;
  margin-top:60px;
}
.split.reverse{direction:rtl}
.split.reverse > *{direction:ltr}
.split-visual{
  position:relative;
  aspect-ratio:1/1;
  max-width:500px;
  margin:0 auto;
  border-radius:24px;
  background:linear-gradient(135deg,rgba(59,130,246,0.1),rgba(139,92,246,0.1));
  border:1px solid var(--border-2);
  overflow:hidden;
  display:grid;place-items:center;
}
.split-visual::before{
  content:'';position:absolute;inset:-50%;
  background:conic-gradient(from 0deg,transparent,rgba(59,130,246,0.3),transparent 30%);
  animation:rotate 8s linear infinite;
}
@keyframes rotate{to{transform:rotate(360deg)}}
.split-visual > *{position:relative;z-index:1}
.split-text h3{font-size:clamp(24px,2.6vw,32px);font-weight:800;letter-spacing:-0.02em;margin-bottom:16px}
.split-text p{color:var(--muted);font-size:16px;line-height:1.75;margin-bottom:20px}
.split-text ul{list-style:none;display:grid;gap:12px}
.split-text li{display:flex;gap:12px;color:var(--text);font-size:15px}
.tick{flex:0 0 24px;width:24px;height:24px;border-radius:8px;background:rgba(16,185,129,0.15);color:var(--emerald);display:grid;place-items:center;font-size:12px;font-weight:800;margin-top:2px;border:1px solid rgba(16,185,129,0.3)}
.cross{flex:0 0 24px;width:24px;height:24px;border-radius:8px;background:rgba(244,63,94,0.15);color:var(--rose);display:grid;place-items:center;font-size:12px;font-weight:800;margin-top:2px;border:1px solid rgba(244,63,94,0.3)}

/* Trial banner */
.trial-banner{
  position:relative;overflow:hidden;
  background:linear-gradient(135deg,rgba(59,130,246,0.15),rgba(139,92,246,0.15));
  border:1px solid var(--border-2);
  border-radius:28px;
  padding:60px 40px;
  text-align:center;
  backdrop-filter:blur(10px);
}
.trial-banner::before{
  content:'';position:absolute;inset:0;
  background:radial-gradient(circle at 20% 50%,rgba(59,130,246,0.3),transparent 50%),radial-gradient(circle at 80% 50%,rgba(139,92,246,0.3),transparent 50%);
  pointer-events:none;
}
.trial-banner > *{position:relative;z-index:1}
.trial-badge{
  display:inline-flex;align-items:center;gap:10px;
  background:var(--grad);color:#fff;
  border-radius:999px;padding:8px 20px;
  font-size:12px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;
  box-shadow:0 10px 30px -8px rgba(59,130,246,0.6);
  margin-bottom:20px;
}
.trial-banner h2{font-size:clamp(28px,3.6vw,48px);font-weight:800;letter-spacing:-0.02em;line-height:1.15}
.trial-banner p{margin-top:16px;color:var(--muted);font-size:17px;max-width:600px;margin-left:auto;margin-right:auto}
.trial-perks{margin-top:28px;display:flex;flex-wrap:wrap;justify-content:center;gap:24px;color:var(--text);font-size:14px;font-weight:600}
.trial-perks span{display:inline-flex;align-items:center;gap:8px}
.trial-perks span::before{content:'✓';color:var(--emerald);font-weight:800}

/* Features */
.cards3{margin-top:56px;display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
.cards2{margin-top:56px;display:grid;grid-template-columns:repeat(2,1fr);gap:20px}
.card{
  position:relative;
  background:var(--surface);
  border:1px solid var(--border);
  border-radius:20px;
  padding:32px;
  transition:all .3s;
  overflow:hidden;
}
.card::before{
  content:'';position:absolute;top:0;left:0;right:0;height:1px;
  background:linear-gradient(90deg,transparent,rgba(59,130,246,0.5),transparent);
  opacity:0;transition:opacity .3s;
}
.card:hover{
  transform:translateY(-6px);
  border-color:rgba(59,130,246,0.3);
  background:var(--surface-2);
  box-shadow:0 20px 60px -20px rgba(59,130,246,0.3);
}
.card:hover::before{opacity:1}
.ic-tile{
  width:52px;height:52px;border-radius:14px;
  display:grid;place-items:center;
  background:var(--grad);
  box-shadow:0 10px 30px -10px rgba(59,130,246,0.6);
  position:relative;overflow:hidden;
}
.ic-tile svg{width:26px;height:26px;position:relative;z-index:1}
.card h3{margin-top:20px;font-size:19px;font-weight:700;letter-spacing:-0.01em}
.card p{margin-top:10px;font-size:14px;line-height:1.7;color:var(--muted)}

/* How it works */
.steps{margin-top:56px;display:grid;grid-template-columns:repeat(3,1fr);gap:24px;position:relative}
.steps::before{
  content:'';position:absolute;top:40px;left:15%;right:15%;height:2px;
  background:linear-gradient(90deg,transparent,var(--brand),var(--violet),var(--cyan),transparent);
  opacity:0.3;
}
.step{
  background:var(--surface);
  border:1px solid var(--border);
  border-radius:20px;
  padding:32px;
  text-align:center;
  position:relative;z-index:1;
  transition:all .3s;
}
.step:hover{border-color:rgba(59,130,246,0.3);transform:translateY(-4px)}
.step-num{
  width:56px;height:56px;border-radius:50%;
  background:var(--grad);color:#fff;
  display:grid;place-items:center;
  font-size:22px;font-weight:800;
  margin:0 auto 20px;
  box-shadow:0 10px 30px -8px rgba(59,130,246,0.6);
  position:relative;
}
.step-num::after{
  content:'';position:absolute;inset:-6px;border-radius:50%;
  border:2px solid rgba(59,130,246,0.3);
  animation:ring 2s ease-out infinite;
}
@keyframes ring{0%{transform:scale(0.9);opacity:1}100%{transform:scale(1.3);opacity:0}}
.step h3{font-size:20px;font-weight:800;margin-bottom:10px}
.step p{font-size:14px;line-height:1.7;color:var(--muted)}

/* FAQ */
.faq-list{margin-top:48px;max-width:820px;margin-left:auto;margin-right:auto}
details.faq{
  background:var(--surface);
  border:1px solid var(--border);
  border-radius:16px;
  overflow:hidden;
  margin-top:12px;
  transition:all .2s;
}
details.faq:hover{border-color:var(--border-2)}
details.faq[open]{border-color:rgba(59,130,246,0.3);background:var(--surface-2)}
details.faq summary{
  list-style:none;
  display:flex;justify-content:space-between;align-items:center;gap:12px;
  padding:20px 24px;font-weight:600;cursor:pointer;
}
details.faq summary::-webkit-details-marker{display:none}
details.faq summary::after{
  content:'⌄';color:var(--faint);font-size:18px;transition:.2s;flex-shrink:0;
}
details[open].faq summary::after{transform:rotate(180deg);color:var(--brand-2)}
details.faq p{padding:0 24px 20px;font-size:14px;line-height:1.7;color:var(--muted)}

/* CTA */
.cta-final{
  position:relative;overflow:hidden;
  border-radius:28px;
  background:linear-gradient(135deg,#1e3a8a,#3b82f6 50%,#8b5cf6);
  padding:72px 40px;
  text-align:center;
  color:#fff;
  box-shadow:0 30px 80px -30px rgba(59,130,246,0.6);
}
.cta-final::before{
  content:'';position:absolute;inset:0;
  background-image:
    radial-gradient(circle at 20% 30%,rgba(255,255,255,0.1),transparent 40%),
    radial-gradient(circle at 80% 70%,rgba(255,255,255,0.1),transparent 40%);
}
.cta-final > *{position:relative;z-index:1}
.cta-final h2{font-size:clamp(26px,3.6vw,42px);font-weight:800;letter-spacing:-0.02em}
.cta-final p{margin:16px auto 0;max-width:600px;color:rgba(255,255,255,0.85);font-size:17px}
.cta-final .btn{margin-top:32px}
.btn.white{background:#fff;color:#1e3a8a;font-weight:700}
.btn.white:hover{transform:translateY(-2px);box-shadow:0 14px 40px -10px rgba(255,255,255,0.4)}

/* Footer */
footer{border-top:1px solid var(--border);padding:40px 0;position:relative;z-index:1}
.foot{display:flex;flex-wrap:wrap;gap:16px;align-items:center;justify-content:space-between}
.foot small{color:var(--faint);font-size:13px}
.foot nav{display:flex;gap:20px;font-size:13px;font-weight:600;color:var(--muted)}
.foot nav a:hover{color:var(--text)}

/* Modal */
.modal{
  position:fixed;inset:0;z-index:80;
  display:none;place-items:center;
  background:rgba(5,8,26,0.8);
  backdrop-filter:blur(10px);
  padding:16px;
  overflow-y:auto;
}
.modal.open{display:grid;animation:fadeIn .2s}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
.modal-card{
  width:100%;max-width:440px;
  background:linear-gradient(180deg,#0f1740,#0a0f2e);
  border:1px solid var(--border-2);
  border-radius:24px;
  padding:36px 32px;
  box-shadow:0 30px 80px -20px rgba(0,0,0,0.8),0 0 60px -20px rgba(59,130,246,0.3);
  animation:pop .3s ease-out;
  max-height:calc(100vh - 32px);
  overflow-y:auto;
}
@keyframes pop{from{opacity:0;transform:scale(.94) translateY(20px)}to{opacity:1;transform:scale(1) translateY(0)}}
.modal-card .logo{margin:0 auto;width:52px;height:52px;font-size:22px}
.modal-card h3{margin-top:16px;font-size:22px;font-weight:800;text-align:center;letter-spacing:-0.02em}
.modal-card .sub{margin-top:6px;font-size:14px;color:var(--muted);text-align:center}
.field{margin-top:16px}
.field label{display:block;margin-bottom:6px;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}
.field input{
  width:100%;
  background:var(--surface);
  border:1px solid var(--border-2);
  border-radius:12px;
  padding:12px 14px;
  font-size:14px;
  color:var(--text);
  outline:none;
  transition:.2s;
  font-family:inherit;
}
.field input::placeholder{color:var(--faint)}
.field input:focus{border-color:var(--brand);box-shadow:0 0 0 4px rgba(59,130,246,0.15);background:var(--surface-2)}
.divider{display:flex;align-items:center;gap:12px;margin:20px 0;color:var(--faint);font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase}
.divider span{flex:1;height:1px;background:var(--border)}
.google-btn{
  display:flex;align-items:center;justify-content:center;gap:10px;
  width:100%;padding:12px;
  background:var(--surface-2);
  border:1px solid var(--border-2);
  border-radius:12px;
  font-weight:700;color:var(--text);
  transition:.2s;
}
.google-btn:hover{background:var(--surface-3);border-color:rgba(59,130,246,0.4)}
.hint{margin-top:16px;font-size:13px;color:var(--muted);text-align:center}
.hint a{color:var(--brand-2);font-weight:700}
.hint a:hover{text-decoration:underline}
.modal-close{position:absolute;top:16px;right:16px;width:36px;height:36px;border-radius:10px;background:var(--surface-2);display:grid;place-items:center;color:var(--muted);transition:.2s}
.modal-close:hover{background:var(--surface-3);color:var(--text)}

/* Mobile drawer */
.mobile-drawer-overlay{position:fixed;inset:0;background:rgba(5,8,26,0.7);backdrop-filter:blur(4px);z-index:65;display:none}
.mobile-drawer-overlay.open{display:block}
.mobile-drawer{
  position:fixed;top:0;right:0;bottom:0;
  width:min(320px,85vw);
  background:linear-gradient(180deg,#0f1740,#05081a);
  border-left:1px solid var(--border);
  box-shadow:-12px 0 40px rgba(0,0,0,0.5);
  z-index:70;
  transform:translateX(100%);
  transition:transform .3s ease;
  display:flex;flex-direction:column;
  padding:24px;
  overflow-y:auto;
}
.mobile-drawer.open{transform:translateX(0)}
.drawer-head{display:flex;justify-content:space-between;align-items:center;padding-bottom:20px;border-bottom:1px solid var(--border);margin-bottom:20px}
.drawer-close{width:36px;height:36px;border-radius:10px;background:var(--surface-2);display:grid;place-items:center;color:var(--text)}
.drawer-nav{display:flex;flex-direction:column;gap:4px;margin-bottom:24px}
.drawer-nav a{padding:14px 16px;border-radius:12px;font-size:15px;font-weight:600;color:var(--text);transition:.15s}
.drawer-nav a:hover{background:var(--surface-2);color:var(--brand-2)}
.drawer-cta{display:flex;flex-direction:column;gap:10px;margin-top:auto;padding-top:20px;border-top:1px solid var(--border)}
.drawer-cta .btn{width:100%;justify-content:center}

/* Scroll animations */
.reveal{opacity:0;transform:translateY(30px);transition:all .8s cubic-bezier(0.16,1,0.3,1)}
.reveal.visible{opacity:1;transform:translateY(0)}

/* Responsive */
@media(max-width:1020px){
  .cards3{grid-template-columns:repeat(2,1fr)}
  .split{gap:40px}
}
@media(max-width:860px){
  .links{display:none}
  .nav-cta{display:none}
  .menu-toggle{display:flex}
  .hero{padding:130px 0 70px}
  .hero-grid{grid-template-columns:1fr;gap:50px}
  .mock-card{transform:none;animation:floatMockMobile 6s ease-in-out infinite}
  @keyframes floatMockMobile{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
  .chip{display:none}
  .stats{grid-template-columns:repeat(2,1fr);padding:24px}
  .split{grid-template-columns:1fr;gap:40px}
  .split.reverse{direction:ltr}
  .split-visual{max-width:400px}
  .cards3{grid-template-columns:1fr}
  .cards2{grid-template-columns:1fr}
  .steps{grid-template-columns:1fr;gap:16px}
  .steps::before{display:none}
  section{padding:70px 0}
  .trial-banner{padding:48px 24px}
  .cta-final{padding:56px 24px}
  .foot{flex-direction:column;text-align:center;gap:12px}
  .modal{padding:0;align-items:flex-end}
  .modal.open{display:flex;align-items:flex-end;justify-content:center}
  .modal-card{border-radius:24px 24px 0 0;max-width:100%;max-height:92vh;padding:28px 20px;animation:slideUp .3s ease-out}
  @keyframes slideUp{from{transform:translateY(100%)}to{transform:translateY(0)}}
}
@media(max-width:480px){
  .hero{padding:110px 0 50px}
  h1.big{font-size:32px}
  .cta-row{flex-direction:column;align-items:stretch}
  .cta-row .btn{width:100%;justify-content:center;padding:14px}
  .stats b{font-size:24px}
  .trial-banner h2{font-size:26px}
}
@supports (-webkit-touch-callout:none){
  @media(max-width:860px){input,select,textarea{font-size:16px !important}}
}
</style>
</head>
<body>

<!-- Background effects -->
<div class="bg-grid"></div>
<div class="bg-orb o1"></div>
<div class="bg-orb o2"></div>
<div class="bg-orb o3"></div>

<!-- NAV -->
<header class="nav">
<div class="nav-inner">
  <a class="brand" href="#">
    <span class="logo">
      <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
    </span>
    AZ Kejora <em>SaaS</em>
  </a>
  <nav class="links">
    <a href="#features">Features</a>
    <a href="#how">How It Works</a>
    <a href="#pricing">Pricing</a>
    <a href="#faq">FAQ</a>
  </nav>
  <div class="nav-cta">
    <button class="btn ghost" onclick="openAuth()">Sign in</button>
    <button class="btn primary" onclick="openSignup()">Start Free Trial</button>
  </div>
  <button class="menu-toggle" onclick="openDrawer()" aria-label="Open menu">☰</button>
</div>
</header>

<!-- MOBILE DRAWER -->
<div class="mobile-drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
<aside class="mobile-drawer" id="mobileDrawer">
  <div class="drawer-head">
    <span class="brand"><span class="logo" style="width:30px;height:30px"><svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg></span>AZ Kejora <em>SaaS</em></span>
    <button class="drawer-close" onclick="closeDrawer()" aria-label="Close">✕</button>
  </div>
  <nav class="drawer-nav">
    <a href="#features" onclick="closeDrawer()">Features</a>
    <a href="#how" onclick="closeDrawer()">How It Works</a>
    <a href="#pricing" onclick="closeDrawer()">Pricing</a>
    <a href="#faq" onclick="closeDrawer()">FAQ</a>
  </nav>
  <div class="drawer-cta">
    <button class="btn ghost" onclick="closeDrawer();openAuth()">Sign in</button>
    <button class="btn primary" onclick="closeDrawer();openSignup()">Start Free Trial</button>
  </div>
</aside>

<!-- HERO -->
<section class="hero">
<div class="wrap">
  <div class="hero-grid">
    <div>
      <span class="eyebrow"><span class="dot"></span>Trusted by 500+ Businesses</span>
      <h1 class="big">Simplify your business with <span class="grad-text">one powerful platform.</span></h1>
      <p class="lead">Stop drowning in spreadsheets, missed deadlines, and scattered data. AZ Kejora brings everything together — dashboards, reports, alerts, and insights — in one elegant workspace designed for modern teams.</p>
      <div class="cta-row">
        <button class="btn primary" style="padding:15px 28px;font-size:16px" onclick="openSignup()">Start 30-Day Free Trial →</button>
        <a class="btn ghost" style="padding:15px 24px;font-size:16px" href="#features">Explore Features</a>
      </div>
      <div class="checks">
        <span>No credit card required</span>
        <span>Cancel anytime</span>
        <span>Setup in 2 minutes</span>
      </div>
    </div>
    <div class="mock">
      <div class="mock-card">
        <div class="mock-top"><span></span><span></span><span></span><i>app.azkejora.io / dashboard</i></div>
        <div class="mock-grid">
          <div class="mock-kpi"><b>2,847</b><p>Active Users</p></div>
          <div class="mock-kpi g"><b>+24.8%</b><p>Growth</p></div>
          <div class="mock-kpi c"><b>RM 1.2M</b><p>Revenue</p></div>
        </div>
        <div class="mock-bars">
          <i style="height:35%"></i><i style="height:52%"></i><i style="height:44%"></i>
          <i style="height:66%"></i><i style="height:58%"></i><i style="height:78%"></i>
          <i style="height:70%"></i><i style="height:92%"></i>
        </div>
        <div class="mock-line">
          <svg viewBox="0 0 400 60" preserveAspectRatio="none">
            <defs>
              <linearGradient id="lineGrad" x1="0" x2="1">
                <stop offset="0%" stop-color="#3b82f6"/>
                <stop offset="50%" stop-color="#8b5cf6"/>
                <stop offset="100%" stop-color="#22d3ee"/>
              </linearGradient>
            </defs>
            <path d="M0,45 Q50,30 100,35 T200,20 T300,25 T400,10" stroke="url(#lineGrad)" stroke-width="2" fill="none"/>
            <path d="M0,45 Q50,30 100,35 T200,20 T300,25 T400,10 L400,60 L0,60 Z" fill="url(#lineGrad)" opacity="0.1"/>
          </svg>
        </div>
      </div>
      <div class="chip" style="left:-10px;top:30px">
        <span class="ic" style="background:rgba(16,185,129,0.15);color:var(--emerald);border:1px solid rgba(16,185,129,0.3)">✓</span>
        <div><b>Report generated</b><small>Q4 analytics ready</small></div>
      </div>
      <div class="chip" style="right:-10px;bottom:40px;animation-delay:1.4s">
        <span class="ic" style="background:rgba(59,130,246,0.15);color:var(--brand-2);border:1px solid rgba(59,130,246,0.3)">🔔</span>
        <div><b>New alert</b><small>Goal reached · +18%</small></div>
      </div>
    </div>
  </div>

  <div class="stats reveal">
    <div><b>500+</b><p>Businesses served</p></div>
    <div><b>2.4M</b><p>Tasks automated</p></div>
    <div><b>99.9%</b><p>Uptime guaranteed</p></div>
    <div><b>24/7</b><p>Expert support</p></div>
  </div>
</div>
</section>

<!-- PROBLEM / SOLUTION -->
<section id="problem">
<div class="wrap">
  <div class="sec-head reveal">
    <span class="eyebrow"><span class="dot"></span>The Challenge</span>
    <h2 class="sec">Running a business shouldn't feel like <span class="grad-text">managing chaos.</span></h2>
    <p>From public users to business owners, everyone deserves tools that work as hard as they do. Yet most teams are still stuck with fragmented systems that create more problems than they solve.</p>
  </div>

  <div class="split reveal">
    <div class="split-visual">
      <svg viewBox="0 0 300 300" width="80%" style="filter:drop-shadow(0 20px 40px rgba(244,63,94,0.3))">
        <defs>
          <linearGradient id="probGrad" x1="0" x2="1">
            <stop offset="0%" stop-color="#f43f5e"/>
            <stop offset="100%" stop-color="#8b5cf6"/>
          </linearGradient>
        </defs>
        <!-- Scattered papers -->
        <g transform="translate(60,80) rotate(-15)">
          <rect width="100" height="130" rx="6" fill="rgba(255,255,255,0.9)" stroke="#f43f5e" stroke-width="2"/>
          <line x1="15" y1="25" x2="85" y2="25" stroke="#cbd5e1" stroke-width="2"/>
          <line x1="15" y1="40" x2="70" y2="40" stroke="#cbd5e1" stroke-width="2"/>
          <line x1="15" y1="55" x2="80" y2="55" stroke="#cbd5e1" stroke-width="2"/>
          <line x1="15" y1="70" x2="60" y2="70" stroke="#cbd5e1" stroke-width="2"/>
          <text x="50" y="110" text-anchor="middle" fill="#f43f5e" font-size="24" font-weight="800">!</text>
        </g>
        <g transform="translate(150,110) rotate(12)">
          <rect width="100" height="130" rx="6" fill="rgba(255,255,255,0.9)" stroke="#f43f5e" stroke-width="2"/>
          <line x1="15" y1="25" x2="85" y2="25" stroke="#cbd5e1" stroke-width="2"/>
          <line x1="15" y1="40" x2="70" y2="40" stroke="#cbd5e1" stroke-width="2"/>
          <line x1="15" y1="55" x2="80" y2="55" stroke="#cbd5e1" stroke-width="2"/>
          <text x="50" y="110" text-anchor="middle" fill="#f43f5e" font-size="24" font-weight="800">?</text>
        </g>
        <!-- Warning icon -->
        <g transform="translate(130,40)">
          <circle cx="20" cy="20" r="20" fill="#f43f5e" opacity="0.2"/>
          <circle cx="20" cy="20" r="14" fill="#f43f5e"/>
          <text x="20" y="26" text-anchor="middle" fill="white" font-size="18" font-weight="800">!</text>
        </g>
      </svg>
    </div>
    <div class="split-text">
      <h3>The old way is broken.</h3>
      <p>Manual data entry eats up hours. Important updates slip through the cracks. Reports take days to compile. And when something goes wrong, you're the last to know. Sound familiar?</p>
      <ul>
        <li><span class="cross">✕</span>Hours wasted on repetitive manual tasks</li>
        <li><span class="cross">✕</span>Scattered data across multiple platforms</li>
        <li><span class="cross">✕</span>No real-time visibility into what matters</li>
        <li><span class="cross">✕</span>Missed deadlines and costly mistakes</li>
      </ul>
    </div>
  </div>

  <div class="split reverse reveal" style="margin-top:80px">
    <div class="split-visual">
      <svg viewBox="0 0 300 300" width="80%" style="filter:drop-shadow(0 20px 40px rgba(59,130,246,0.4))">
        <defs>
          <linearGradient id="solGrad" x1="0" x2="1">
            <stop offset="0%" stop-color="#3b82f6"/>
            <stop offset="100%" stop-color="#22d3ee"/>
          </linearGradient>
        </defs>
        <!-- Dashboard mockup -->
        <rect x="40" y="60" width="220" height="160" rx="12" fill="#0f1740" stroke="url(#solGrad)" stroke-width="2"/>
        <rect x="50" y="75" width="200" height="20" rx="4" fill="rgba(59,130,246,0.2)"/>
        <circle cx="60" cy="85" r="3" fill="#f43f5e"/>
        <circle cx="70" cy="85" r="3" fill="#f59e0b"/>
        <circle cx="80" cy="85" r="3" fill="#10b981"/>
        <!-- KPI cards -->
        <rect x="55" y="105" width="60" height="40" rx="6" fill="rgba(59,130,246,0.15)" stroke="rgba(59,130,246,0.3)"/>
        <text x="85" y="122" text-anchor="middle" fill="#60a5fa" font-size="11" font-weight="800">+24%</text>
        <text x="85" y="138" text-anchor="middle" fill="#9aa6c7" font-size="7">GROWTH</text>
        <rect x="120" y="105" width="60" height="40" rx="6" fill="rgba(16,185,129,0.15)" stroke="rgba(16,185,129,0.3)"/>
        <text x="150" y="122" text-anchor="middle" fill="#10b981" font-size="11" font-weight="800">1,248</text>
        <text x="150" y="138" text-anchor="middle" fill="#9aa6c7" font-size="7">TASKS</text>
        <rect x="185" y="105" width="60" height="40" rx="6" fill="rgba(139,92,246,0.15)" stroke="rgba(139,92,246,0.3)"/>
        <text x="215" y="122" text-anchor="middle" fill="#8b5cf6" font-size="11" font-weight="800">98%</text>
        <text x="215" y="138" text-anchor="middle" fill="#9aa6c7" font-size="7">UPTIME</text>
        <!-- Chart -->
        <path d="M55,190 L90,175 L125,180 L160,165 L195,170 L230,155 L245,150" stroke="url(#solGrad)" stroke-width="2" fill="none"/>
        <path d="M55,190 L90,175 L125,180 L160,165 L195,170 L230,155 L245,150 L245,210 L55,210 Z" fill="url(#solGrad)" opacity="0.15"/>
        <!-- Checkmark -->
        <g transform="translate(230,40)">
          <circle cx="20" cy="20" r="20" fill="#10b981" opacity="0.2"/>
          <circle cx="20" cy="20" r="14" fill="#10b981"/>
          <path d="M14,20 L18,24 L26,16" stroke="white" stroke-width="2.5" fill="none" stroke-linecap="round"/>
        </g>
      </svg>
    </div>
    <div class="split-text">
      <h3>There's a smarter way.</h3>
      <p>AZ Kejora unifies your entire workflow into one intuitive platform. Automate the tedious, visualize the important, and act on what matters — all from a single dashboard that works wherever you do.</p>
      <ul>
        <li><span class="tick">✓</span>Automate repetitive tasks in seconds</li>
        <li><span class="tick">✓</span>Unified data across all your tools</li>
        <li><span class="tick">✓</span>Real-time insights at your fingertips</li>
        <li><span class="tick">✓</span>Smart alerts before problems happen</li>
      </ul>
    </div>
  </div>
</div>
</section>

<!-- 30-DAY TRIAL -->
<section id="pricing">
<div class="wrap">
  <div class="trial-banner reveal">
    <span class="trial-badge">⚡ Limited Offer</span>
    <h2>Try AZ Kejora free for <span class="grad-text">30 full days.</span></h2>
    <p>Experience every feature, every integration, every insight — completely free for a month. No credit card. No commitments. Just results.</p>
    <div class="trial-perks">
      <span>Full feature access</span>
      <span>No credit card</span>
      <span>Cancel anytime</span>
      <span>Priority onboarding</span>
    </div>
    <button class="btn primary" style="margin-top:32px;padding:16px 32px;font-size:16px" onclick="openSignup()">Start My Free 30-Day Trial →</button>
  </div>
</div>
</section>

<!-- FEATURES -->
<section id="features">
<div class="wrap">
  <div class="sec-head reveal">
    <span class="eyebrow"><span class="dot"></span>Powerful Features</span>
    <h2 class="sec">Everything you need. <span class="grad-text">Nothing you don't.</span></h2>
    <p>Built for individuals, teams, and businesses of every size. Simple enough to start in minutes, powerful enough to scale with you.</p>
  </div>

  <div class="cards3">
    <div class="card reveal">
      <div class="ic-tile">
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      </div>
      <h3>Simplified Dashboard</h3>
      <p>A clean, intuitive command center that surfaces what matters most. See your KPIs, tasks, and trends at a glance — no clutter, no confusion.</p>
    </div>
    <div class="card reveal">
      <div class="ic-tile" style="background:linear-gradient(135deg,#10b981,#22d3ee)">
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12" y2="18"/></svg>
      </div>
      <h3>Accessible Anywhere</h3>
      <p>Desktop, tablet, or phone — your workspace travels with you. Secure cloud access means your data is always one tap away, from anywhere in the world.</p>
    </div>
    <div class="card reveal">
      <div class="ic-tile" style="background:linear-gradient(135deg,#f59e0b,#f43f5e)">
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      </div>
      <h3>Reporting & Download</h3>
      <p>Generate beautiful, audit-ready reports in one click. Export to PDF, Excel, or CSV — perfect for stakeholders, compliance, or your own records.</p>
    </div>
    <div class="card reveal">
      <div class="ic-tile" style="background:linear-gradient(135deg,#8b5cf6,#3b82f6)">
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
      </div>
      <h3>Data Tracking & Monitoring</h3>
      <p>Watch your metrics come alive in real-time. Track trends, spot anomalies, and monitor performance with precision dashboards built for clarity.</p>
    </div>
    <div class="card reveal">
      <div class="ic-tile" style="background:linear-gradient(135deg,#22d3ee,#8b5cf6)">
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
      </div>
      <h3>Alerts & Notifications</h3>
      <p>Never miss what matters. Smart alerts notify you instantly via email, SMS, or in-app — so you can act fast when it counts, and relax when it doesn't.</p>
    </div>
    <div class="card reveal">
      <div class="ic-tile" style="background:linear-gradient(135deg,#f43f5e,#8b5cf6)">
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      </div>
      <h3>Bank-Grade Security</h3>
      <p>Your data is encrypted at rest and in transit. Role-based access, audit logs, and compliance-ready architecture keep everything safe and sound.</p>
    </div>
  </div>
</div>
</section>

<!-- HOW IT WORKS -->
<section id="how" style="background:linear-gradient(180deg,transparent,rgba(15,23,64,0.4),transparent)">
<div class="wrap">
  <div class="sec-head reveal">
    <span class="eyebrow"><span class="dot"></span>Simple 3-Step Workflow</span>
    <h2 class="sec">From sign-up to success in <span class="grad-text">minutes.</span></h2>
    <p>No complicated setup. No steep learning curves. Just three simple steps to transform how you work.</p>
  </div>
  <div class="steps">
    <div class="step reveal">
      <div class="step-num">1</div>
      <h3>Sign Up Free</h3>
      <p>Create your account in under 60 seconds. No credit card required. Get instant access to your personalized dashboard.</p>
    </div>
    <div class="step reveal">
      <div class="step-num">2</div>
      <h3>Connect & Configure</h3>
      <p>Import your data or connect your existing tools. Our guided setup walks you through every step — no technical skills needed.</p>
    </div>
    <div class="step reveal">
      <div class="step-num">3</div>
      <h3>Grow & Scale</h3>
      <p>Start automating, tracking, and reporting. Watch your productivity soar as AZ Kejora handles the heavy lifting for you.</p>
    </div>
  </div>
</div>
</section>

<!-- FAQ -->
<section id="faq">
<div class="wrap">
  <div class="sec-head reveal">
    <span class="eyebrow"><span class="dot"></span>FAQ</span>
    <h2 class="sec">Questions, answered.</h2>
  </div>
  <div class="faq-list">
    <details class="faq" open><summary>Who is AZ Kejora designed for?</summary><p>AZ Kejora is built for everyone — public users managing personal projects, small business owners streamlining operations, and enterprise teams scaling complex workflows. Our flexible platform adapts to your needs, whatever they may be.</p></details>
    <details class="faq"><summary>How does the 30-day free trial work?</summary><p>Sign up with just your email — no credit card needed. You get full access to every feature for 30 complete days. If you love it, choose a plan. If not, walk away with no obligations whatsoever.</p></details>
    <details class="faq"><summary>Can I access AZ Kejora on my phone?</summary><p>Absolutely. AZ Kejora is fully responsive and works beautifully on desktop, tablet, and mobile. Your data syncs in real-time across all your devices, so you're always up to date.</p></details>
    <details class="faq"><summary>Is my data secure?</summary><p>Yes. We use bank-grade encryption (AES-256) for data at rest and TLS 1.3 for data in transit. Role-based access controls, audit logs, and regular security audits ensure your information stays protected.</p></details>
    <details class="faq"><summary>What kind of reports can I generate?</summary><p>Generate custom reports with our drag-and-drop builder. Export to PDF, Excel, or CSV. Schedule automatic reports to be emailed to stakeholders weekly, monthly, or on any custom schedule.</p></details>
    <details class="faq"><summary>Can I cancel anytime?</summary><p>Yes — cancel with one click from your dashboard. No penalties, no hidden fees, no awkward phone calls. Your data remains accessible for 30 days after cancellation so you can export anything you need.</p></details>
  </div>
</div>
</section>

<!-- FINAL CTA -->
<section>
<div class="wrap">
  <div class="cta-final reveal">
    <h2>Ready to simplify everything?</h2>
    <p>Join thousands of users who've transformed their workflow with AZ Kejora. Start your 30-day free trial today — no credit card, no commitment, just results.</p>
    <button class="btn white" onclick="openSignup()">Start My Free Trial →</button>
  </div>
</div>
</section>

<footer>
<div class="wrap foot">
  <span class="brand" style="font-size:15px">
    <span class="logo" style="width:28px;height:28px;border-radius:8px"><svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg></span>
    AZ Kejora <em>SaaS</em>
  </span>
  <small>© 2026 AZ Kejora SaaS · Simplified Solutions for Everyone</small>
  <nav><a href="#features">Features</a><a href="#pricing">Pricing</a><a href="#faq">FAQ</a></nav>
</div>
</footer>

<!-- SIGN IN MODAL -->
<div class="modal" id="authModal" onclick="if(event.target===this)closeAuth()">
<div class="modal-card" style="position:relative">
  <button class="modal-close" onclick="closeAuth()" aria-label="Close">✕</button>
  <span class="logo">
    <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
  </span>
  <h3>Welcome back</h3>
  <p class="sub">Sign in to your AZ Kejora account</p>

  <a href="#" class="google-btn" style="margin-top:20px" onclick="event.preventDefault();alert('Google sign-in integration placeholder')">
    <svg width="18" height="18" viewBox="0 0 24 24">
      <path fill="#4285F4" d="M23.49 12.27c0-.79-.07-1.54-.19-2.27H12v4.51h6.47c-.29 1.48-1.14 2.73-2.4 3.58v3h3.86c2.26-2.09 3.56-5.17 3.56-8.82z"/>
      <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.86-3c-1.08.72-2.45 1.16-4.07 1.16-3.13 0-5.78-2.11-6.73-4.96H1.29v3.09C3.26 21.3 7.31 24 12 24z"/>
      <path fill="#FBBC05" d="M5.27 14.29c-.25-.72-.38-1.49-.38-2.29s.14-1.57.38-2.29V6.62H1.29C.47 8.24 0 10.06 0 12s.47 3.76 1.29 5.38l3.98-3.09z"/>
      <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.31 0 3.26 2.7 1.29 6.62l3.98 3.09C6.22 6.86 8.87 4.75 12 4.75z"/>
    </svg>
    Continue with Google
  </a>

  <div class="divider"><span></span>OR<span></span></div>

  <form onsubmit="event.preventDefault();alert('Sign-in integration placeholder')">
    <div class="field"><label>Email address</label><input type="email" placeholder="you@example.com" required></div>
    <div class="field"><label>Password</label><input type="password" placeholder="Enter your password" required></div>
    <button type="submit" class="btn primary full" style="margin-top:20px">Sign in securely</button>
  </form>

  <p class="hint">Don't have an account? <a href="#" onclick="closeAuth();openSignup();return false">Start free trial</a></p>
</div>
</div>

<!-- SIGN UP MODAL -->
<div class="modal" id="signupModal" onclick="if(event.target===this)closeSignup()">
<div class="modal-card" style="position:relative">
  <button class="modal-close" onclick="closeSignup()" aria-label="Close">✕</button>
  <span class="logo">
    <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
  </span>
  <h3>Start your free trial</h3>
  <p class="sub">30 days full access · No credit card required</p>

  <a href="#" class="google-btn" style="margin-top:20px" onclick="event.preventDefault();alert('Google sign-up integration placeholder')">
    <svg width="18" height="18" viewBox="0 0 24 24">
      <path fill="#4285F4" d="M23.49 12.27c0-.79-.07-1.54-.19-2.27H12v4.51h6.47c-.29 1.48-1.14 2.73-2.4 3.58v3h3.86c2.26-2.09 3.56-5.17 3.56-8.82z"/>
      <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.86-3c-1.08.72-2.45 1.16-4.07 1.16-3.13 0-5.78-2.11-6.73-4.96H1.29v3.09C3.26 21.3 7.31 24 12 24z"/>
      <path fill="#FBBC05" d="M5.27 14.29c-.25-.72-.38-1.49-.38-2.29s.14-1.57.38-2.29V6.62H1.29C.47 8.24 0 10.06 0 12s.47 3.76 1.29 5.38l3.98-3.09z"/>
      <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.31 0 3.26 2.7 1.29 6.62l3.98 3.09C6.22 6.86 8.87 4.75 12 4.75z"/>
    </svg>
    Sign up with Google
  </a>

  <div class="divider"><span></span>OR<span></span></div>

  <form onsubmit="event.preventDefault();alert('Sign-up integration placeholder')">
    <div class="field"><label>Full name</label><input type="text" placeholder="Your name" required></div>
    <div class="field"><label>Email address</label><input type="email" placeholder="you@example.com" required></div>
    <div class="field"><label>Password</label><input type="password" placeholder="At least 6 characters" minlength="6" required></div>
    <button type="submit" class="btn primary full" style="margin-top:20px">Start 30-Day Free Trial →</button>
  </form>

  <p class="hint">Already have an account? <a href="#" onclick="closeSignup();openAuth();return false">Sign in</a></p>
</div>
</div>

<script>
// Modal controls
function openAuth(){document.getElementById('authModal').classList.add('open');document.body.style.overflow='hidden';}
function closeAuth(){document.getElementById('authModal').classList.remove('open');document.body.style.overflow='';}
function openSignup(){document.getElementById('signupModal').classList.add('open');document.body.style.overflow='hidden';}
function closeSignup(){document.getElementById('signupModal').classList.remove('open');document.body.style.overflow='';}

// Mobile drawer
function openDrawer(){
  document.getElementById('mobileDrawer').classList.add('open');
  document.getElementById('drawerOverlay').classList.add('open');
  document.body.style.overflow='hidden';
}
function closeDrawer(){
  document.getElementById('mobileDrawer').classList.remove('open');
  document.getElementById('drawerOverlay').classList.remove('open');
  document.body.style.overflow='';
}
document.querySelectorAll('.drawer-nav a').forEach(a=>{
  a.addEventListener('click',()=>setTimeout(closeDrawer,50));
});

// Escape key
document.addEventListener('keydown',e=>{
  if(e.key==='Escape'){closeAuth();closeSignup();closeDrawer();}
});

// Scroll reveal
const observer=new IntersectionObserver((entries)=>{
  entries.forEach(entry=>{
    if(entry.isIntersecting){
      entry.target.classList.add('visible');
      observer.unobserve(entry.target);
    }
  });
},{threshold:0.1,rootMargin:'0px 0px -50px 0px'});
document.querySelectorAll('.reveal').forEach(el=>observer.observe(el));

// Parallax orbs on mouse move
document.addEventListener('mousemove',(e)=>{
  const x=(e.clientX/window.innerWidth-0.5)*20;
  const y=(e.clientY/window.innerHeight-0.5)*20;
  document.querySelector('.bg-orb.o1').style.transform=`translate(${x}px,${y}px)`;
  document.querySelector('.bg-orb.o2').style.transform=`translate(${-x}px,${-y}px)`;
  document.querySelector('.bg-orb.o3').style.transform=`translate(${x*0.5}px,${-y*0.5}px)`;
});
</script>
</body>
</html>
