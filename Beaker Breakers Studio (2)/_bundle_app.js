// ============ STATE ============
const STATE = {
  xp: 620,
  selectedAvatar: { img: window.__resources.avGirl1, name:'Aisha' },
  tierProgress: { 1:100, 2:60, 3:0, 4:0 }, // percent per tier
  completedExps: new Set(),
  missions: { exp:0, quiz:0, ar:0 },
  faves: new Set(),
  currentExp: null,
  currentTier: null,
  quizState: null,
};

// ============ NAV ============
const navStack = [];
let currentPage = 'page-home';
const tabPageMap = {
  'tb-home':'page-home', 'tb-exp':'page-experiments',
  'tb-safety':'page-safety', 'tb-lb':'page-leaderboard', 'tb-profile':'page-profile'
};

function showPage(pageId) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.getElementById(pageId).classList.add('active');
  window.scrollTo(0, 0);
  currentPage = pageId;
  // hide mascot on AR
  const mascot = document.getElementById('mascot');
  if (mascot) mascot.style.display = pageId === 'page-ar' ? 'none' : 'block';
}

function goToPage(pageId, fromPage) {
  navStack.push(fromPage || currentPage);
  showPage(pageId);
  updateNavs(pageId);
}

function goBack() {
  if (navStack.length > 0) {
    const prev = navStack.pop();
    showPage(prev);
    updateNavs(prev);
    if (prev === 'page-profile') renderProfile();
    if (prev === 'page-leaderboard') renderLeaderboard('class');
    if (prev === 'page-safety') renderSafety();
  } else {
    showPage('page-home');
    updateNavs('page-home');
  }
}

function navTo(pageId) {
  navStack.length = 0;
  showPage(pageId);
  updateNavs(pageId);
  closeSidebarMobile();
  if (pageId === 'page-profile') renderProfile();
  if (pageId === 'page-leaderboard') renderLeaderboard('class');
}

function updateNavs(pageId) {
  document.querySelectorAll('.taskbar-btn').forEach(b => b.classList.remove('active'));
  for (const [tab, page] of Object.entries(tabPageMap)) {
    if (page === pageId) { document.getElementById(tab)?.classList.add('active'); break; }
  }
  document.querySelectorAll('.side-link').forEach(b => {
    b.classList.toggle('active', b.dataset.page === pageId);
  });
}
function updateTaskbar(pageId) { updateNavs(pageId); }

function toggleSidebar() {
  const sb = document.getElementById('sidebar');
  const ov = document.getElementById('side-overlay');
  sb.classList.toggle('open');
  ov.classList.toggle('show');
}
function closeSidebarMobile() {
  document.getElementById('sidebar')?.classList.remove('open');
  document.getElementById('side-overlay')?.classList.remove('show');
}

// ============ UTIL ============
function showToast(msg, icon='🎉') {
  const toast = document.getElementById('toast');
  document.getElementById('toast-text').textContent = msg;
  document.getElementById('toast-icon').textContent = icon;
  toast.classList.add('show');
  clearTimeout(showToast._t);
  showToast._t = setTimeout(() => toast.classList.remove('show'), 2800);
}

function triggerConfetti() {
  const wrap = document.getElementById('confetti');
  wrap.innerHTML = '';
  wrap.classList.add('show');
  const colors = ['#FFD54F','#4FC3F7','#EF5350','#66BB6A','#CE93D8','#FF8A50','#26C6DA'];
  for (let i=0; i<60; i++) {
    const el = document.createElement('div');
    el.className = 'conf-piece';
    el.style.left = Math.random()*100 + 'vw';
    el.style.background = colors[Math.floor(Math.random()*colors.length)];
    el.style.width = (Math.random()*10+6) + 'px';
    el.style.height = (Math.random()*10+6) + 'px';
    el.style.borderRadius = Math.random()>0.5 ? '50%' : '2px';
    el.style.animationDelay = Math.random()*1.2 + 's';
    el.style.animationDuration = (Math.random()*1.5+2) + 's';
    wrap.appendChild(el);
  }
  setTimeout(() => { wrap.classList.remove('show'); wrap.innerHTML=''; }, 4000);
}

function flyXP(amount) {
  const f = document.getElementById('xp-float');
  f.textContent = `+${amount} XP`;
  f.classList.remove('show');
  void f.offsetWidth;
  f.classList.add('show');
}

function addXP(n) {
  STATE.xp += n;
  flyXP(n);
  const home = document.getElementById('home-xp');
  if (home) home.textContent = STATE.xp;
  const tb = document.getElementById('topbar-xp');
  if (tb) tb.textContent = STATE.xp;
  const fill = document.getElementById('home-xp-fill');
  if (fill) fill.style.width = Math.min(100, (STATE.xp / 1000) * 100) + '%';
  updateRank();
}

function updateRank() {
  let rank = 'Beginner';
  if (STATE.xp >= 1500) rank = 'Master Chemist';
  else if (STATE.xp >= 1000) rank = 'Senior Chemist';
  else if (STATE.xp >= 500) rank = 'Junior Chemist';
  else if (STATE.xp >= 200) rank = 'Apprentice';
  const r = document.getElementById('home-rank');
  if (r) r.textContent = `⭐ ${rank}`;
  const sr = document.getElementById('side-rank');
  if (sr) sr.textContent = `⭐ ${rank}`;
  const hhn = document.getElementById('hh-name');
  if (hhn) hhn.textContent = STATE.selectedAvatar.name;
}

// ============ HOME RENDER ============
function renderAvatars() {
  const strip = document.getElementById('avatar-strip');
  strip.innerHTML = AVATARS.map((a,i) => `
    <div class="avatar-card ${i===0?'selected':''}" onclick="selectAvatar(this,'${a.img}','${a.name}')">
      <img class="av-img" src="${a.img}" alt="${a.name}">
      <div class="av-name">${a.name}</div>
    </div>
  `).join('');
}

function selectAvatar(card, img, name) {
  document.querySelectorAll('.avatar-card').forEach(c => c.classList.remove('selected'));
  card.classList.add('selected');
  STATE.selectedAvatar = { img, name };
  const homeAv = document.getElementById('home-av');
  if (homeAv) homeAv.innerHTML = `<img src="${img}" alt="${name}">`;
  const sideAv = document.getElementById('side-av');
  if (sideAv) sideAv.innerHTML = `<img src="${img}" alt="${name}">`;
  const sideName = document.getElementById('side-name');
  if (sideName) sideName.textContent = `Scientist ${name}`;
  showMascotMessage(`${name} is ready to do science! 🔬`);
  showToast(`You picked ${name}!`, '✨');
}

// ============ EXPERIMENTS ============
let activeFilter = 'All';
function renderFilters() {
  const row = document.getElementById('filter-row');
  row.innerHTML = FILTERS.map(f => `
    <button class="filter-pill ${f===activeFilter?'active':''}" onclick="setFilter('${f}')">${f}</button>
  `).join('');
}
function setFilter(f) { activeFilter = f; renderFilters(); renderExperiments(); }

function renderExperiments() {
  const grid = document.getElementById('exp-grid');
  let items = EXPERIMENTS;
  if (activeFilter !== 'All') {
    if (activeFilter === '⭐ Faves') items = EXPERIMENTS.filter(e => STATE.faves.has(e.id));
    else items = EXPERIMENTS.filter(e => activeFilter.includes(e.cat));
  }
  grid.innerHTML = items.map(e => {
    const done = STATE.completedExps.has(e.id);
    const locked = e.locked;
    return `
      <div class="exp-card ${locked?'locked':''}" onclick="${locked?`showToast('🔒 Complete Safety Training to unlock!','🔒')`:`openExp('${e.id}')`}">
        <div class="exp-thumb" style="background:${e.bg}">
          ${e.emoji}${e.badge && !locked ? `<span class="exp-badge" style="background:${e.badge==='HOT'?'var(--orange)':'var(--blue)'};">${e.badge}</span>` : ''}
          ${done ? `<span class="exp-badge" style="background:var(--green); top:auto; bottom:8px; left:8px; right:auto;">✓ DONE</span>` : ''}
        </div>
        <div class="exp-info">
          <div class="exp-title">${e.title}</div>
          <div class="exp-meta">
            <span class="exp-tag ${locked?'locked':''}">${e.diff}</span>
            ${e.diff2 && !locked ? `<span class="exp-tag">${e.diff2}</span>` : ''}
          </div>
        </div>
      </div>
    `;
  }).join('');
  if (!items.length) {
    grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:40px 20px;color:var(--subtext);font-weight:800;">No experiments match this filter 🔎</div>`;
  }
}

function openExp(id) {
  const e = EXPERIMENTS.find(x => x.id === id);
  STATE.currentExp = e;
  const isFave = STATE.faves.has(id);
  const body = document.getElementById('exp-detail-body');
  body.innerHTML = `
    <div class="exp-hero" style="background:${e.bg}">
      <div class="emoji-big">${e.emoji}</div>
      <div class="exp-h-title">${e.title}</div>
      <div class="exp-h-meta">${e.diff2 || ''} • ${e.diff} • ${e.time}</div>
      <div class="exp-xp-badge">+${e.xp} XP</div>
    </div>

    <p style="color:var(--text);font-weight:700;font-size:14px;line-height:1.55;margin-bottom:18px;">${e.desc}</p>

    <div class="section-label">📋 Steps</div>
    <div class="steps-list">
      ${e.steps.map((s,i) => `
        <div class="step-row" style="border-left-color: ${['var(--green)','var(--blue)','var(--orange)'][i%3]};">
          <div class="step-num" style="background:${['var(--green)','var(--blue)','var(--orange)'][i%3]}">${i+1}</div>
          <div><div class="step-t">${s.t}</div><div class="step-d">${s.d}</div></div>
        </div>
      `).join('')}
    </div>

    <button class="btn-primary" onclick="startRunner()">🚀 Start Experiment!</button>
    <button class="btn-ghost" onclick="startARFor('${e.id}')">🕶️ Try in AR Mode</button>
    <button class="btn-ghost" onclick="toggleFave('${e.id}')" style="border-color:var(--pink);color:var(--pink);">${isFave?'💔 Remove from Faves':'⭐ Add to Faves'}</button>

    <div class="ai-help">
      <div class="ai-help-ic">🤖</div>
      <div><h4>Ask Beaky AI</h4><p>"Why does this reaction make bubbles?" — Tap and ask me anything! 💬</p></div>
    </div>
  `;
  goToPage('page-exp-detail');
}

function toggleFave(id) {
  if (STATE.faves.has(id)) STATE.faves.delete(id);
  else { STATE.faves.add(id); showToast('Added to favourites! ⭐', '⭐'); }
  openExp(id);
}

// ============ EXPERIMENT RUNNER ============
let RUN = null;
function startRunner() {
  const e = STATE.currentExp;
  RUN = { exp:e, step:0, used:new Set() };
  goToPage('page-exp-runner');
  const title = document.getElementById('runner-title');
  if (title) title.innerHTML = `<span style="font-size:24px;margin-right:6px;">${e.emoji||'🧪'}</span>${e.title}`;
  renderRunner();
}
function renderRunner() {
  const { exp, step } = RUN;
  const s = exp.steps[step];
  const body = document.getElementById('runner-body');
  const total = exp.steps.length;

  const av = STATE.selectedAvatar;
  const tip = s.tip || s.d;
  const progressPct = Math.round((step / total) * 100);

  // Build pip dots
  const pips = exp.steps.map((_,i) =>
    `<span class="pip ${i<step?'done':i===step?'active':''}"></span>`
  ).join('');

  // Build bench items - the current one is highlighted, prior ones are placed, future ones are queued
  const benchItems = exp.steps.map((st,i) => {
    const state = i<step ? 'placed' : i===step ? 'active' : 'queued';
    return `
      <div class="bench-slot ${state}">
        <div class="bench-emoji" data-idx="${i}"
             ${i===step ? `draggable="true" onclick="completeStep()" ondragstart="startDrag(event,${i})"` : ''}>
          ${st.item}
        </div>
        <div class="bench-label">${i===step ? 'TAP ME' : (i<step ? '✓' : i+1)}</div>
      </div>
    `;
  }).join('');

  body.innerHTML = `
    <!-- Top: progress -->
    <div class="rn-progress">
      <div class="rn-progress-info">
        <span class="rn-step-num">Step ${step+1} of ${total}</span>
        <span class="rn-pct">${progressPct}%</span>
      </div>
      <div class="rn-progress-bar"><div class="rn-progress-fill" style="width:${progressPct}%"></div></div>
      <div class="rn-pips">${pips}</div>
    </div>

    <!-- Coach card (small avatar, clean tip) -->
    <div class="rn-coach">
      <div class="rn-coach-avatar">
        <img src="${av.img}" alt="${av.name}" />
      </div>
      <div class="rn-coach-body">
        <div class="rn-coach-head">
          <span class="rn-coach-name">Scientist ${av.name}</span>
          <span class="rn-coach-role">your guide</span>
        </div>
        <div class="rn-coach-tip">${tip}</div>
      </div>
    </div>

    <!-- Task card -->
    <div class="rn-task-card">
      <div class="rn-task-num">${step+1}</div>
      <div class="rn-task-text">
        <div class="rn-task-title">${s.t}</div>
        <div class="rn-task-action">${s.action ? '👉 ' + s.action : '👉 Tap the glowing item below'}</div>
      </div>
    </div>

    <!-- Lab bench: items + target -->
    <div class="rn-bench-wrap">
      <div class="rn-bench-target" id="target-zone">
        <div class="rn-target-ring"></div>
        <div class="rn-target-icon">🎯</div>
        <div class="rn-target-label">DROP ZONE</div>
        <div class="rn-reaction-fx" id="reaction-fx"></div>
      </div>
      <div class="rn-bench-row">
        ${benchItems}
      </div>
      <div class="rn-bench-table"></div>
    </div>

    <!-- Explanation -->
    <div class="rn-explain">
      <div class="rn-explain-icon">🔬</div>
      <div class="rn-explain-body">
        <div class="rn-explain-title">The Science</div>
        <div class="rn-explain-text">${s.d}</div>
      </div>
    </div>

    <!-- Controls -->
    <div class="rn-controls">
      <button class="btn-ghost" onclick="arPrevStepRunner()" ${step===0?'disabled':''}>‹ Prev Step</button>
      <button class="btn-primary rn-do-btn" onclick="completeStep()">${s.action || 'Do it!'} →</button>
    </div>
  `;

  // setup drag target
  const zone = document.getElementById('target-zone');
  if (zone) {
    zone.ondragover = (e) => { e.preventDefault(); zone.classList.add('over'); };
    zone.ondragleave = () => zone.classList.remove('over');
    zone.ondrop = (e) => { e.preventDefault(); zone.classList.remove('over'); completeStep(); };
  }
}

function startDrag(e, idx) {
  if (e.dataTransfer) e.dataTransfer.setData('text/plain', idx);
}

function arPrevStepRunner() { if (RUN.step>0) { RUN.step--; renderRunner(); } }

function completeStep() {
  const { exp, step } = RUN;
  const s = exp.steps[step];

  // reaction effect on eruption
  if (s.erupt) {
    const fx = document.getElementById('reaction-fx');
    fx.innerHTML = '';
    const colors = ['#FF8A50','#FFD54F','#EF5350','#FF6D00','#FFAB40'];
    for (let i=0;i<40;i++) {
      const p = document.createElement('div');
      p.className = 'fx-particle';
      const size = 6 + Math.random()*14;
      p.style.width = size+'px'; p.style.height = size+'px';
      p.style.background = colors[Math.floor(Math.random()*colors.length)];
      p.style.left = (40 + Math.random()*20) + '%';
      p.style.bottom = '40px';
      p.style.setProperty('--dx', (Math.random()-0.5)*260 + 'px');
      p.style.animationDelay = (Math.random()*0.4) + 's';
      fx.appendChild(p);
    }
  }

  setTimeout(() => {
    RUN.step++;
    if (RUN.step >= exp.steps.length) {
      finishExperiment();
    } else {
      renderRunner();
    }
  }, s.erupt ? 1400 : 400);
}

function finishExperiment() {
  const e = RUN.exp;
  STATE.completedExps.add(e.id);
  STATE.missions.exp = Math.min(1, STATE.missions.exp + 1);
  addXP(e.xp);
  triggerConfetti();
  showToast(`🎉 ${e.title} complete! +${e.xp} XP`, '🎉');
  showMascotMessage('Amazing! You finished the experiment! 🌟');
  refreshMissions();

  const body = document.getElementById('runner-body');
  body.innerHTML = `
    <div class="quiz-result" style="margin-top:40px;">
      <div class="emoji-big">🏆</div>
      <h2>Experiment Complete!</h2>
      <p>You earned <strong style="color:var(--orange)">+${e.xp} XP</strong>! Great job, Scientist ${STATE.selectedAvatar.name}!</p>
      <div style="display:flex;gap:10px;margin-top:20px;flex-wrap:wrap;justify-content:center;">
        <button class="btn-ghost" style="margin:0;flex:1;min-width:130px;" onclick="navTo('page-experiments')">More Labs →</button>
        <button class="btn-primary" style="margin:0;flex:1;min-width:130px;" onclick="startARFor('${e.id}')">🕶️ Try in AR</button>
      </div>
    </div>
  `;
}

function exitRunner() {
  if (confirm('Exit this experiment? Your progress will be lost.')) {
    goBack();
  }
}

// ============ SAFETY / TIERS ============
function renderSafety() {
  const list = document.getElementById('tier-list');
  list.innerHTML = TIERS.map((t,i) => {
    const prev = i===0 ? 100 : STATE.tierProgress[i]; // previous tier's progress
    const isLocked = i>0 && STATE.tierProgress[i] < 100; // previous not done
    // actually unlock if previous tier == 100
    const prevDone = i===0 ? true : STATE.tierProgress[i] >= 100;
    const progress = STATE.tierProgress[t.n];
    const completed = progress >= 100;
    const locked = !prevDone;
    return `
      <div class="tier-card ${completed?'completed':''} ${locked?'locked':''}"
           onclick="${locked?`showToast('🔒 Complete the previous tier first','🔒')`:`startTier(${t.n})`}">
        <div class="tier-num t${t.n}">${t.n}</div>
        <div class="tier-info">
          <div class="tier-title">${t.title}</div>
          <div class="tier-desc">${t.desc}</div>
          <div class="tier-progress"><div class="tier-progress-fill" style="width:${progress}%; ${completed?'background:var(--green);':''}"></div></div>
        </div>
        <div class="tier-status">${completed?'✅':locked?'🔒':'⏳'}</div>
      </div>
    `;
  }).join('');

  // cert state
  const allDone = TIERS.every(t => STATE.tierProgress[t.n] >= 100);
  const card = document.getElementById('cert-card');
  const btn = document.getElementById('cert-btn');
  const sub = document.getElementById('cert-sub');
  if (allDone) {
    card.classList.add('unlocked');
    btn.disabled = false;
    btn.textContent = '🏅 View Certificate';
    sub.textContent = 'Congratulations! You are officially certified! 🎉';
  } else {
    card.classList.remove('unlocked');
    btn.disabled = true;
    btn.textContent = '🔒 Locked';
    sub.textContent = 'Complete all 4 tiers to unlock.';
  }
}

function startTier(n) {
  STATE.currentTier = n;
  const tier = TIERS.find(t => t.n === n);
  STATE.quizState = { qi:0, correct:0, tier };
  goToPage('page-quiz');
  renderQuiz();
}

function renderQuiz() {
  const { qi, tier, correct } = STATE.quizState;
  const total = tier.questions.length;
  const q = tier.questions[qi];
  const body = document.getElementById('quiz-body');
  body.innerHTML = `
    <div class="quiz-header">
      <div class="quiz-icon">🧪</div>
      <div class="quiz-title">Tier ${tier.n}: ${tier.title.replace(/^\S+\s/,'')}</div>
      <div class="quiz-sub">Question ${qi+1} of ${total} • Score 80% to pass</div>
    </div>
    <div class="quiz-progress"><div class="quiz-progress-fill" style="width:${((qi)/total)*100}%"></div></div>
    <div class="quiz-card">
      <div class="quiz-q-ic">${q.ic}</div>
      <div class="quiz-q">${q.q}</div>
    </div>
    <div class="quiz-options">
      ${q.opts.map((o,i) => `
        <button class="quiz-opt" onclick="answerQuiz(this,${i})">
          <span class="opt-ic">${o.ic}</span> ${o.t}
        </button>
      `).join('')}
    </div>
  `;
}

function answerQuiz(btn, idx) {
  const qs = STATE.quizState;
  const q = qs.tier.questions[qs.qi];
  const all = document.querySelectorAll('.quiz-opt');
  all.forEach(b => b.disabled = true);

  if (idx === q.a) {
    btn.classList.add('correct');
    qs.correct++;
    STATE.missions.quiz = Math.min(3, STATE.missions.quiz + 1);
    showMascotMessage('Amazing! You got it right! 🌟');
  } else {
    btn.classList.add('wrong');
    all[q.a]?.classList.add('correct');
    showMascotMessage('Almost! Read the question again carefully 📚');
  }

  setTimeout(() => {
    qs.qi++;
    if (qs.qi >= qs.tier.questions.length) {
      finishQuiz();
    } else {
      renderQuiz();
    }
  }, 1100);
}

function finishQuiz() {
  const { tier, correct } = STATE.quizState;
  const total = tier.questions.length;
  const pct = Math.round((correct/total)*100);
  const passed = pct >= 80;

  if (passed) {
    STATE.tierProgress[tier.n] = 100;
    addXP(100);
    triggerConfetti();
  } else {
    STATE.tierProgress[tier.n] = Math.max(STATE.tierProgress[tier.n], pct);
  }

  refreshMissions();
  const body = document.getElementById('quiz-body');
  body.innerHTML = `
    <div class="quiz-result">
      <div class="emoji-big">${passed?'🎉':'💪'}</div>
      <h2>${passed?'You passed!':'Try again!'}</h2>
      <div class="quiz-score-ring" style="--pct:${pct}%"><span>${pct}%</span></div>
      <p>${passed?`Tier ${tier.n} complete! +100 XP`:`You need 80% to pass. Don't give up!`}</p>
      <div style="display:flex;gap:10px;margin-top:18px;flex-wrap:wrap;">
        <button class="btn-ghost" style="margin:0;flex:1;" onclick="exitQuiz()">Back to Safety</button>
        <button class="btn-primary" style="margin:0;flex:1;" onclick="startTier(${tier.n})">${passed?'Retake':'Try Again'}</button>
      </div>
    </div>
  `;
}

function exitQuiz() {
  navStack.length = 0;
  showPage('page-safety');
  updateTaskbar('page-safety');
  renderSafety();
}

function viewCertificate() {
  document.getElementById('cert-name').textContent = `Scientist ${STATE.selectedAvatar.name}`;
  document.getElementById('cert-date').textContent = new Date().toLocaleDateString('en-GB',{year:'numeric',month:'short',day:'numeric'});
  document.getElementById('cert-modal').classList.add('show');
  triggerConfetti();
}
function closeCert(e) {
  if (e && e.target.id !== 'cert-modal' && !e.target.classList.contains('cert-close')) return;
  document.getElementById('cert-modal').classList.remove('show');
}

// ============ LEADERBOARD ============
function setLbTab(el, tab) {
  document.querySelectorAll('.lb-tab').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
  renderLeaderboard(tab);
}
function renderLeaderboard(tab='class') {
  const rows = LEADERBOARD[tab];
  const top3 = rows.slice(0,3);
  const rest = rows.slice(3);
  const podium = document.getElementById('podium');
  podium.innerHTML = `
    ${top3[1] ? podiumItem(top3[1], 'rank2', '🥈') : ''}
    ${top3[0] ? podiumItem(top3[0], 'rank1', '🥇') : ''}
    ${top3[2] ? podiumItem(top3[2], 'rank3', '🥉') : ''}
  `;
  const list = document.getElementById('lb-list');
  list.innerHTML = rest.map(r => `
    <div class="lb-row ${r.me?'me':''}">
      <div class="lb-rank">${r.r}</div>
      <div class="lb-av">${r.me?`<img src="${STATE.selectedAvatar.img}" alt="">`:r.av}</div>
      <div class="lb-name">${r.n}</div>
      <div class="lb-pts">${r.me?STATE.xp:r.xp} XP</div>
      <div class="lb-badge">${r.b}</div>
    </div>
  `).join('');
}
function podiumItem(r, cls, medal) {
  return `
    <div class="podium-item ${cls}">
      <div class="podium-avatar">${r.me?`<img src="${STATE.selectedAvatar.img}" alt="">`:r.av}</div>
      <div class="podium-name">${r.n}</div>
      <div class="podium-xp">${r.me?STATE.xp:r.xp} XP</div>
      <div class="podium-stand">${medal}</div>
    </div>
  `;
}

// ============ PROFILE ============
function renderProfile() {
  const body = document.getElementById('profile-body');
  const av = STATE.selectedAvatar;
  const donesExp = STATE.completedExps.size;
  const certified = Object.values(STATE.tierProgress).filter(p=>p>=100).length;

  body.innerHTML = `
    <div class="profile-header">
      <span class="profile-av"><img src="${av.img}" alt="${av.name}"></span>
      <div class="profile-username">Scientist ${av.name}</div>
      <div class="profile-school">🏫 The University of Chenab, Gujrat</div>
      <div class="profile-badges-row">
        <span class="p-badge">⭐ ${rankName()}</span>
        <span class="p-badge">🛡️ ${certified}/4 Tiers</span>
      </div>
    </div>
    <div class="stats-grid">
      ${stat('⚡', STATE.xp, 'Total XP')}
      ${stat('🔬', donesExp, 'Experiments')}
      ${stat('🔥', 5, 'Day Streak')}
      ${stat('🏅', 12, 'Badges')}
      ${stat('🎯', '87%', 'Accuracy')}
      ${stat('🏆', '#6', 'Class Rank')}
    </div>
    <div class="section-label">🎖️ My Badges</div>
    <div class="badge-chips">
      ${BADGES.map(b => `
        <div class="badge-chip ${b.locked?'locked':''}" style="background:${b.bg};">
          <span class="bc-icon">${b.ic}</span>${b.name}
        </div>
      `).join('')}
    </div>
    <div class="readiness">
      <h4>🤖 AI Readiness Report</h4>
      <p><strong>Status: 🟡 Approaching Readiness</strong><br>
      You've shown great progress in Procedural Accuracy and Safety Compliance! Complete Tier 2, 3 & 4 safety training to be <strong>Ready for Real Lab</strong>! 🧪</p>
      <div class="readiness-chips">
        <span class="rd-chip" style="color:var(--green-d);">✅ Safety: ${certified*25}%</span>
        <span class="rd-chip" style="color:var(--blue-d);">✅ Accuracy: 87%</span>
        <span class="rd-chip" style="color:var(--orange-d);">⏳ Certification: ${certified*25}%</span>
      </div>
    </div>
  `;
}
function stat(ic, v, lbl) {
  return `<div class="stat-card"><div class="stat-icon">${ic}</div><div class="stat-val">${v}</div><div class="stat-lbl">${lbl}</div></div>`;
}
function rankName() {
  if (STATE.xp >= 1500) return 'Master Chemist';
  if (STATE.xp >= 1000) return 'Senior Chemist';
  if (STATE.xp >= 500) return 'Junior Chemist';
  if (STATE.xp >= 200) return 'Apprentice';
  return 'Beginner';
}

// ============ MASCOT ============
const mascotTips = [
  "Always wear goggles in a real lab! 🥽",
  "Never mix chemicals without asking! ⚠️",
  "Complete safety training to unlock more experiments! 🛡️",
  "Earn XP by finishing experiments! ⚡",
  "Check the leaderboard to see your rank! 🏆",
  "AR Mode lets you see experiments in YOUR room! 🕶️",
  "Ask me anything during an experiment! 🤖",
  "5-day streaks earn bonus badges! 🔥",
];
let mascotIdx = 0;
function cycleMascot() {
  mascotIdx = (mascotIdx+1) % mascotTips.length;
  showMascotMessage(mascotTips[mascotIdx]);
}
function showMascotMessage(msg) { document.getElementById('mascot-text').textContent = msg; }
setInterval(cycleMascot, 8000);

// ============ MISSIONS ============
function refreshMissions() {
  document.querySelector('#m-exp .m-fill').style.width  = (STATE.missions.exp/1*100) + '%';
  document.querySelector('#m-quiz .m-fill').style.width = (STATE.missions.quiz/3*100) + '%';
  document.querySelector('#m-ar .m-fill').style.width   = (STATE.missions.ar/1*100) + '%';
  if (STATE.missions.exp>=1)  document.getElementById('m-exp').classList.add('done');
  if (STATE.missions.quiz>=3) document.getElementById('m-quiz').classList.add('done');
  if (STATE.missions.ar>=1)   document.getElementById('m-ar').classList.add('done');
}

// ============ INIT ============
renderAvatars();
renderFilters();
renderExperiments();
renderSafety();
renderLeaderboard('class');
refreshMissions();
updateRank();
const fill = document.getElementById('home-xp-fill');
if (fill) fill.style.width = Math.min(100,(STATE.xp/1000)*100)+'%';
