// ============ DATA ============
const AVATARS = [
  { img:'avatars/avatar-girl1.png', name:'Rabail Butt' },
  { img:'avatars/avatar-boy1.png',  name:'Mohsin Abbas' },
  { img:'avatars/avatar-girl2.png', name:'Hina'  },
  { img:'avatars/avatar-boy2.png',  name:'Zayn'  },
];

const FILTERS = ['All', '🔥 Reactions', '💧 Solutions', '⚗️ Mixtures', '🌡️ Heat', '⭐ Faves'];

const EXPERIMENTS = [
  { id:'volcano', title:'Volcano Eruption', emoji:'🌋', badge:'HOT', cat:'Reactions',
    diff:'⭐ Easy', diff2:'🔥 Acid+Base', xp:50, time:'20 min',
    bg:'linear-gradient(135deg,#FFF3E0,#FFCC80)',
    desc:'Classic baking-soda + vinegar eruption. Learn acid-base neutralisation!',
    steps:[
      { t:'Build Your Volcano Model 🏔️', d:'Shape a cone from paper around an empty cup. That cup is your crater!', item:'🏔️', action:'Place cone', tip:'Hi scientist! First, let\'s build the mountain. Use tape to hold the cone shape!' },
      { t:'Add Baking Soda 🥄',           d:'Drop 2 tablespoons of baking soda into the cup. Baking soda is a BASE.', item:'🥄', action:'Add baking soda', tip:'Nice! Now drop 2 spoons of baking soda inside. Careful not to spill — it\'s a BASE!' },
      { t:'Pour the Vinegar! 🍶',         d:'Slowly pour vinegar in. Watch the explosion! Vinegar is an ACID.', item:'🍶', action:'Pour vinegar', erupt:true, tip:'Stand back a little and pour the vinegar slowly. 3… 2… 1… ERUPTION! 🌋' },
    ]
  },
  { id:'bluebottle', title:'Blue Bottle Magic', emoji:'🔵', badge:'NEW', cat:'Solutions',
    diff:'⭐⭐ Medium', diff2:'💧 Redox', xp:80, time:'15 min',
    bg:'linear-gradient(135deg,#E3F2FD,#90CAF9)',
    desc:'A colourless solution turns blue when shaken, then fades again. Magic? No — it\'s redox!',
    steps:[
      { t:'Mix the Solution 🧪', d:'Combine glucose, sodium hydroxide and methylene blue in a bottle.', item:'🧪', action:'Mix reagents', tip:'Wear gloves! Sodium hydroxide is caustic. Mix gently in the bottle.' },
      { t:'Shake It Up! 🫨',      d:'Shake the bottle — oxygen enters and oxidises the dye to blue.', item:'🫨', action:'Shake bottle', tip:'Now shake! Oxygen dissolves in and turns the dye bright blue — like magic!' },
      { t:'Wait & Observe ⏳',   d:'Glucose reduces the dye back to colourless. Repeat as many times as you like!', item:'⏳', action:'Observe', tip:'Set it down and count to 20. The blue will fade — that\'s REDOX in action!' },
    ]
  },
  { id:'chromat', title:'Chromatography', emoji:'🌿', cat:'Mixtures',
    diff:'⭐ Easy', diff2:'🎨 Colours', xp:40, time:'10 min',
    bg:'linear-gradient(135deg,#E8F5E9,#A5D6A7)',
    desc:'Split a marker ink into its separate colours using paper and water.',
    steps:[
      { t:'Draw a Line ✏️',    d:'Draw a thick line of ink near the bottom of filter paper.', item:'✏️', action:'Draw line', tip:'Pick a black marker — those have the most hidden colours inside. Draw a fat line!' },
      { t:'Dip in Water 💧',   d:'Place the paper so water just touches the bottom — not the line.', item:'💧', action:'Dip paper', tip:'Gently dip — water should be BELOW your ink line, not over it!' },
      { t:'Watch Colours Split 🌈', d:'Different dyes travel at different speeds — chromatography!', item:'🌈', action:'Observe', tip:'Watch closely! Can you see reds, blues, yellows separating? That\'s chromatography!' },
    ]
  },
  { id:'crystal', title:'Crystal Growing', emoji:'🧊', cat:'Solutions',
    diff:'⭐⭐ Medium', diff2:'❄️ Crystals', xp:60, time:'2 days',
    bg:'linear-gradient(135deg,#FCE4EC,#F8BBD9)',
    desc:'Grow beautiful sugar crystals on a string using supersaturation.',
    steps:[
      { t:'Boil Sugar Water ♨️', d:'Dissolve as much sugar as possible in hot water — supersaturated solution!', item:'♨️', action:'Boil mixture', tip:'Ask an adult for the stove. Keep adding sugar till no more dissolves — that\'s the magic!' },
      { t:'Hang a String 🧵',    d:'Tie a string to a pencil and dip it into the solution.', item:'🧵', action:'Hang string', tip:'Tie the string to a pencil, lay it across the jar. String should hang, not touch the sides.' },
      { t:'Wait for Crystals 💎', d:'After 1–2 days, crystals will grow along the string. Sweet science!', item:'💎', action:'Observe', tip:'Now the hardest part: WAIT! Check each day. Crystals grow overnight ✨' },
    ]
  },
  { id:'ph', title:'pH Indicators', emoji:'⚗️', cat:'Solutions', locked:true,
    diff:'🔒 Locked', xp:70, time:'12 min',
    bg:'linear-gradient(135deg,#F3E5F5,#CE93D8)',
    desc:'Use red-cabbage juice to detect acids and bases. Complete Tier 2 safety to unlock!',
    steps:[]
  },
  { id:'electro', title:'Electrolysis', emoji:'🌊', cat:'Reactions', locked:true,
    diff:'🔒 Locked', xp:100, time:'18 min',
    bg:'linear-gradient(135deg,#E0F7FA,#80DEEA)',
    desc:'Split water into hydrogen and oxygen. Complete Tier 3 safety to unlock!',
    steps:[]
  },
];

const TIERS = [
  { n:1, title:'🧯 Basic Lab Rules', desc:'Never eat in the lab • Wear goggles • Stay calm',
    questions:[
      { q:'What should you wear in the lab?', ic:'🥽', a:1, opts:[
        {ic:'👟', t:'Sandals'}, {ic:'🥽', t:'Safety goggles'}, {ic:'🧣', t:'A scarf'}, {ic:'👑', t:'A crown'},
      ]},
      { q:'Is eating in the lab allowed?', ic:'🍕', a:1, opts:[
        {ic:'😋', t:'Yes, snacks are fine'}, {ic:'🚫', t:'Never — chemicals can contaminate food'},
        {ic:'🤷', t:'Only chips'}, {ic:'🌙', t:'Only at night'},
      ]},
      { q:'Long hair should be…', ic:'💁', a:2, opts:[
        {ic:'💨', t:'Left loose'}, {ic:'✂️', t:'Cut short'},
        {ic:'🎀', t:'Tied back'}, {ic:'🔥', t:'Set on fire'},
      ]},
      { q:'If unsure about a procedure, you should…', ic:'🤔', a:0, opts:[
        {ic:'🙋', t:'Ask the teacher first'}, {ic:'🤞', t:'Guess and hope'},
        {ic:'🏃', t:'Walk out'}, {ic:'😴', t:'Take a nap'},
      ]},
      { q:'Lab coats protect you from…', ic:'🥼', a:1, opts:[
        {ic:'❄️', t:'Cold only'}, {ic:'🧪', t:'Chemical spills'},
        {ic:'☀️', t:'Sunburn'}, {ic:'🦟', t:'Mosquitoes'},
      ]},
    ]},
  { n:2, title:'🧪 Chemical Handling', desc:'Read labels • Never mix unknown chemicals • Dispose safely',
    questions:[
      { q:'You accidentally spill a chemical. You should…', ic:'💧', a:1, opts:[
        {ic:'😴', t:'Ignore it and keep working'}, {ic:'🧹', t:'Inform the teacher & clean it safely'},
        {ic:'🚪', t:'Run out of the room'}, {ic:'💨', t:'Blow on it to dry it'},
      ]},
      { q:'Before using any chemical, you must…', ic:'🔖', a:2, opts:[
        {ic:'🎵', t:'Sing a song'}, {ic:'💧', t:'Rinse with water'},
        {ic:'🔖', t:'Read the label'}, {ic:'🙈', t:'Close your eyes'},
      ]},
      { q:'Never mix two chemicals if you don\'t know…', ic:'⚠️', a:0, opts:[
        {ic:'❓', t:'What they do together'}, {ic:'🎨', t:'Their colour'},
        {ic:'📏', t:'Their price'}, {ic:'🕰️', t:'Their age'},
      ]},
      { q:'Used chemicals should be…', ic:'🗑️', a:2, opts:[
        {ic:'🕳️', t:'Poured down the sink'}, {ic:'🏞️', t:'Thrown outside'},
        {ic:'🏷️', t:'Put in labelled waste containers'}, {ic:'🎁', t:'Taken home'},
      ]},
      { q:'When smelling a chemical, you should…', ic:'👃', a:1, opts:[
        {ic:'💨', t:'Inhale deeply'}, {ic:'👋', t:'Gently waft air toward your nose'},
        {ic:'💋', t:'Kiss the bottle'}, {ic:'🙅', t:'Never smell any chemical'},
      ]},
    ]},
  { n:3, title:'🔥 Fire & Heat Safety', desc:'Handling hot equipment • Emergency procedures',
    questions:[
      { q:'If clothing catches fire, you should…', ic:'🔥', a:2, opts:[
        {ic:'🏃', t:'Run around'}, {ic:'🚿', t:'Jump in water'},
        {ic:'🛑', t:'Stop, drop and roll'}, {ic:'📱', t:'Take a selfie'},
      ]},
      { q:'Hot glassware looks just like…', ic:'🥛', a:0, opts:[
        {ic:'❄️', t:'Cold glassware — always use tongs'}, {ic:'🌶️', t:'It glows red'},
        {ic:'💨', t:'It smokes'}, {ic:'🔔', t:'It makes a sound'},
      ]},
      { q:'Bunsen burner should be lit using…', ic:'🔥', a:1, opts:[
        {ic:'🕯️', t:'A candle'}, {ic:'🪵', t:'A striker — never a match pointing down'},
        {ic:'⚡', t:'Lightning'}, {ic:'🖐️', t:'Your hand'},
      ]},
      { q:'A fire extinguisher in the lab is for…', ic:'🧯', a:0, opts:[
        {ic:'🔥', t:'Any small fire you can safely control'}, {ic:'🎨', t:'Painting walls'},
        {ic:'🥤', t:'Cold drinks'}, {ic:'🎈', t:'Balloons'},
      ]},
      { q:'Long sleeves near an open flame are…', ic:'👕', a:2, opts:[
        {ic:'😎', t:'Stylish'}, {ic:'🟢', t:'Fine'},
        {ic:'⚠️', t:'Dangerous — roll them up'}, {ic:'❄️', t:'Cooling'},
      ]},
    ]},
  { n:4, title:'⚕️ First Aid & Emergency', desc:'Spill response • Eye wash station • Emergency exits',
    questions:[
      { q:'Chemical splashes in your eye. You…', ic:'👁️', a:0, opts:[
        {ic:'🚿', t:'Use the eye wash station for 15 minutes'}, {ic:'😭', t:'Cry it out'},
        {ic:'🍃', t:'Rub with a tissue'}, {ic:'😴', t:'Take a nap'},
      ]},
      { q:'Where are emergency exits?', ic:'🚪', a:1, opts:[
        {ic:'❓', t:'Who knows'}, {ic:'🗺️', t:'Know all of them before starting work'},
        {ic:'🚷', t:'Blocked'}, {ic:'🏕️', t:'Outside'},
      ]},
      { q:'A classmate cuts their hand. You…', ic:'🩹', a:2, opts:[
        {ic:'🏃', t:'Run away'}, {ic:'📸', t:'Take a photo'},
        {ic:'🆘', t:'Call the teacher & apply pressure with clean cloth'}, {ic:'💦', t:'Pour acid on it'},
      ]},
      { q:'First aid kit should be…', ic:'⛑️', a:1, opts:[
        {ic:'🔒', t:'Locked away'}, {ic:'📍', t:'Clearly visible & accessible'},
        {ic:'🏕️', t:'Outside the building'}, {ic:'🎒', t:'In your bag'},
      ]},
      { q:'After any accident, you should…', ic:'📝', a:0, opts:[
        {ic:'📋', t:'Report it to the teacher immediately'}, {ic:'🙈', t:'Hide it'},
        {ic:'🤫', t:'Keep it secret'}, {ic:'🎈', t:'Celebrate'},
      ]},
    ]},
];

const LEADERBOARD = {
  class: [
    { r:1, av:'🦁', n:'Fatima K.',   xp:1850, b:'🏆' },
    { r:2, av:'🐉', n:'Ahmed S.',    xp:1240, b:'🥇' },
    { r:3, av:'🦋', n:'Sara R.',     xp: 980, b:'🥈' },
    { r:4, av:'🐸', n:'Mohsin A.',   xp: 920, b:'🔬' },
    { r:5, av:'🐝', n:'Zara M.',     xp: 810, b:'⚗️' },
    { r:6, av:'🦊', n:'You 🎉',      xp: 620, b:'🌟', me:true },
    { r:7, av:'🦄', n:'Omar T.',     xp: 580, b:'🧪' },
    { r:8, av:'🐧', n:'Hira N.',     xp: 420, b:'🌡️' },
  ],
  school: [
    { r:1, av:'🐝', n:'Ayesha H.',   xp:3420, b:'👑' },
    { r:2, av:'🦁', n:'Fatima K.',   xp:1850, b:'🏆' },
    { r:3, av:'🐲', n:'Bilal R.',    xp:1630, b:'🥇' },
    { r:4, av:'🐉', n:'Ahmed S.',    xp:1240, b:'🥈' },
    { r:5, av:'🦋', n:'Sara R.',     xp: 980, b:'🥉' },
    { r:6, av:'🦊', n:'You 🎉',      xp: 620, b:'🌟', me:true },
    { r:7, av:'🦄', n:'Omar T.',     xp: 580, b:'🧪' },
    { r:8, av:'🐧', n:'Hira N.',     xp: 420, b:'🌡️' },
  ],
  friends: [
    { r:1, av:'🐸', n:'Mohsin A.',   xp: 920, b:'🔬' },
    { r:2, av:'🦊', n:'You 🎉',      xp: 620, b:'🌟', me:true },
    { r:3, av:'🐧', n:'Hira N.',     xp: 420, b:'🌡️' },
  ],
};

const BADGES = [
  { ic:'🌋', name:'Volcano Master', bg:'#FFF9C4' },
  { ic:'🔬', name:'Lab Explorer',   bg:'#E3F2FD' },
  { ic:'🛡️', name:'Safety First',   bg:'#E8F5E9' },
  { ic:'🔥', name:'5 Day Streak',   bg:'#FCE4EC' },
  { ic:'⚡', name:'Speed Scientist', bg:'#EDE7F6' },
  { ic:'💎', name:'Crystal Grower', bg:'#E0F7FA', locked:true },
  { ic:'🎓', name:'Certified',      bg:'#FFE0B2', locked:true },
  { ic:'🕶️', name:'AR Pioneer',     bg:'#E1F5FE', locked:true },
];
