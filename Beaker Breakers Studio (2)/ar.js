// ============ AR MODE ============
let arStream = null;
let arCurrentCamera = 'environment';
let AR = null;

async function startAR() { startARFor('volcano'); }

async function startARFor(expId) {
  const exp = EXPERIMENTS.find(e => e.id === expId) || EXPERIMENTS[0];
  AR = { exp, step:0, placed:false, objects:[] };
  document.getElementById('ar-exp').textContent = exp.title;
  document.getElementById('ar-steps').textContent = exp.steps.length;
  document.getElementById('ar-step').textContent = 1;

  goToPage('page-ar');
  document.getElementById('page-ar').classList.remove('nocam');
  document.getElementById('ar-hud-left').style.display = 'flex';

  // Always draw simulated room behind, in case camera fails
  drawSimulatedRoom();

  // Try camera
  let cameraOk = false;
  try {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      throw new Error('mediaDevices unavailable (insecure context?)');
    }
    if (arStream) { arStream.getTracks().forEach(t => t.stop()); }
    arStream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: arCurrentCamera, width:{ideal:1280}, height:{ideal:720} },
      audio: false
    });
    const video = document.getElementById('ar-video');
    video.srcObject = arStream;
    await video.play();
    cameraOk = true;
  } catch (err) {
    console.log('[AR] camera unavailable, using simulated view:', err.message);
  }

  if (!cameraOk) {
    document.getElementById('page-ar').classList.add('nocam');
  }

  // Mark AR mission progress
  STATE.missions.ar = Math.min(1, STATE.missions.ar + 1);
  refreshMissions();

  resetARScene();
  updateARControls();
}

function drawSimulatedRoom() {
  const canvas = document.getElementById('ar-canvas');
  const ctx = canvas.getContext('2d');
  const resize = () => {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    render();
  };
  const render = () => {
    const w = canvas.width, h = canvas.height;
    // Sky/ceiling
    const g = ctx.createLinearGradient(0, 0, 0, h * 0.55);
    g.addColorStop(0, '#223049'); g.addColorStop(1, '#3A4A66');
    ctx.fillStyle = g; ctx.fillRect(0, 0, w, h * 0.55);
    // Floor
    const g2 = ctx.createLinearGradient(0, h * 0.55, 0, h);
    g2.addColorStop(0, '#2A384F'); g2.addColorStop(1, '#18202F');
    ctx.fillStyle = g2; ctx.fillRect(0, h * 0.55, w, h * 0.45);

    ctx.strokeStyle = 'rgba(140,180,230,0.18)';
    ctx.lineWidth = 1;
    const vx = w / 2, vy = h * 0.55;
    for (let i = -8; i <= 8; i++) {
      ctx.beginPath();
      ctx.moveTo(vx, vy);
      ctx.lineTo(vx + (i / 4) * w, h);
      ctx.stroke();
    }
    for (let i = 1; i <= 10; i++) {
      const t = i / 10;
      const y = vy + t * t * (h - vy);
      ctx.beginPath();
      ctx.moveTo(0, y); ctx.lineTo(w, y); ctx.stroke();
    }
    const grad = ctx.createRadialGradient(w * 0.7, h * 0.3, 20, w * 0.7, h * 0.3, 280);
    grad.addColorStop(0, 'rgba(0,229,255,0.2)'); grad.addColorStop(1, 'rgba(0,229,255,0)');
    ctx.fillStyle = grad; ctx.fillRect(0, 0, w, h);
  };
  resize();
  window.onresize = resize;
}

function flipAR() {
  arCurrentCamera = arCurrentCamera === 'environment' ? 'user' : 'environment';
  if (AR) startARFor(AR.exp.id);
}

function exitAR() {
  if (arStream) { arStream.getTracks().forEach(t => t.stop()); arStream = null; }
  const video = document.getElementById('ar-video');
  if (video) video.srcObject = null;
  goBack();
}

function resetARScene() {
  document.getElementById('ar-scene').innerHTML = '';
  document.getElementById('ar-reticle').classList.remove('hide');
  document.getElementById('ar-scanline').classList.remove('hide');
  document.getElementById('rt-msg').textContent = 'Tap anywhere to place your lab bench';
  document.getElementById('aa-bubble').textContent = 'Tap a flat surface to place your lab bench 🔬';
}

// Single delegated tap handler for AR page
document.getElementById('page-ar').addEventListener('click', (e) => {
  if (!AR) return;
  if (e.target.closest('.ar-top, .ar-bottom, .ar-assistant, .ar-hud-left')) return;
  if (!AR.placed) {
    placeBench(e.clientX, e.clientY);
  } else {
    // Tap to advance / interact with current step
    arDoAction();
  }
});

function placeBench(x, y) {
  AR.placed = true;
  document.getElementById('ar-reticle').classList.add('hide');
  document.getElementById('ar-scanline').classList.add('hide');

  const scene = document.getElementById('ar-scene');
  const bench = document.createElement('div');
  bench.className = 'ar-obj';
  bench.innerHTML = '<div class="ar-bench"></div>';
  bench.style.left = (x || window.innerWidth / 2) + 'px';
  bench.style.top  = (y || window.innerHeight * 0.62) + 'px';
  bench.style.transform = 'translate(-50%,-50%)';
  bench.id = 'ar-bench-root';
  scene.appendChild(bench);

  setTimeout(() => addARObjectForStep(), 350);
  document.getElementById('aa-bubble').textContent = `Great! Now follow the steps for ${AR.exp.title}.`;
  updateARControls();
}

function addARObjectForStep() {
  const scene = document.getElementById('ar-scene');
  scene.querySelectorAll('.ar-step-obj').forEach(n => n.remove());

  const bench = document.getElementById('ar-bench-root');
  if (!bench) return;
  const rect = bench.getBoundingClientRect();
  const cx = rect.left + rect.width / 2;
  const cy = rect.top + rect.height / 2;

  const s = AR.exp.steps[AR.step];
  if (!s) return;

  // Main 3D object
  const obj = document.createElement('div');
  obj.className = 'ar-obj ar-step-obj';
  obj.innerHTML = `<div class="ar-3d-obj">${s.item || AR.exp.emoji}</div>`;
  obj.style.left = cx + 'px';
  obj.style.top  = (cy - 40) + 'px';
  obj.style.transform = 'translate(-50%,-100%)';
  scene.appendChild(obj);

  // Label
  const lbl = document.createElement('div');
  lbl.className = 'ar-obj ar-step-obj ar-label';
  lbl.textContent = s.t.replace(/[^\w\s!?]/g, '').trim().slice(0, 30);
  lbl.style.left = (cx + 80) + 'px';
  lbl.style.top  = (cy - 80) + 'px';
  scene.appendChild(lbl);

  // Eruption particles
  if (s.erupt) {
    for (let i = 0; i < 30; i++) {
      const p = document.createElement('div');
      p.className = 'ar-obj ar-step-obj';
      p.style.position = 'absolute';
      p.style.left = (cx + (Math.random() - 0.5) * 40) + 'px';
      p.style.top  = (cy - 60) + 'px';
      p.style.width = (6 + Math.random() * 14) + 'px';
      p.style.height = p.style.width;
      p.style.borderRadius = '50%';
      const colors = ['#FF8A50','#FFD54F','#EF5350','#FFAB40'];
      p.style.background = colors[Math.floor(Math.random() * colors.length)];
      p.style.animation = `fxRise ${1.6 + Math.random() * 0.8}s ease-out ${Math.random() * 0.3}s forwards`;
      p.style.setProperty('--dx', (Math.random() - 0.5) * 240 + 'px');
      p.style.boxShadow = '0 0 12px ' + p.style.background;
      scene.appendChild(p);
    }
    setTimeout(() => {
      scene.querySelectorAll('.ar-step-obj').forEach(n => {
        if (n.style.animation) n.remove();
      });
    }, 2600);
  }

  document.getElementById('aa-bubble').textContent = s.d;
}

function arNextStep() {
  if (!AR.placed) {
    placeBench(window.innerWidth / 2, window.innerHeight * 0.62);
    return;
  }
  if (AR.step < AR.exp.steps.length - 1) {
    AR.step++;
    document.getElementById('ar-step').textContent = AR.step + 1;
    addARObjectForStep();
    updateARControls();
  } else {
    finishAR();
  }
}

function finishAR() {
  document.getElementById('aa-bubble').textContent = `🎉 Experiment complete! +${AR.exp.xp} XP!`;
  STATE.completedExps.add(AR.exp.id);
  addXP(AR.exp.xp);
  triggerConfetti();
  showToast(`🏆 AR Experiment complete! +${AR.exp.xp} XP`, '🏆');
  setTimeout(() => exitAR(), 2500);
}

function arPrevStep() {
  if (!AR.placed || AR.step === 0) return;
  AR.step--;
  document.getElementById('ar-step').textContent = AR.step + 1;
  addARObjectForStep();
  updateARControls();
}

function arDoAction() {
  if (!AR.placed) {
    placeBench(window.innerWidth / 2, window.innerHeight * 0.62);
  } else {
    // Trigger current step's action visual then advance
    const s = AR.exp.steps[AR.step];
    if (s && s.erupt) {
      addARObjectForStep();
      setTimeout(() => arNextStep(), 1600);
    } else {
      arNextStep();
    }
  }
}

function updateARControls() {
  const prev = document.getElementById('ar-ctrl-prev');
  const next = document.getElementById('ar-ctrl-next');
  const act  = document.getElementById('ar-ctrl-action');
  if (!AR) return;
  if (!AR.placed) {
    prev.disabled = true; next.disabled = true;
    act.textContent = '📍 Place Bench';
  } else {
    prev.disabled = AR.step === 0;
    next.disabled = false;
    const s = AR.exp.steps[AR.step];
    const last = AR.step === AR.exp.steps.length - 1;
    act.textContent = last ? '✨ Finish' : (s.action || 'Do it!');
  }
}
