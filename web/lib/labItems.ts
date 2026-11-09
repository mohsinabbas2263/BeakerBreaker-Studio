// docs/experiment-engine.md: every item id maps to one icon and label, so the same
// item looks the same in every experiment. Ids come from backend/database/seeders/game.

export const ITEMS: Record<string, { emoji: string; label: string }> = {
  // safety gear
  goggles: { emoji: "🥽", label: "Goggles" },
  coat: { emoji: "🥼", label: "Lab coat" },
  gloves: { emoji: "🧤", label: "Gloves" },
  sandals: { emoji: "🩴", label: "Sandals" },
  snack: { emoji: "🍪", label: "Snack" },
  // glassware and apparatus
  beaker: { emoji: "🥛", label: "Beaker" },
  test_tube: { emoji: "🧪", label: "Test tube" },
  conical_flask: { emoji: "⚗️", label: "Conical flask" },
  flask: { emoji: "⚗️", label: "Flask" },
  burette: { emoji: "📏", label: "Burette" },
  glass_tube: { emoji: "🧫", label: "Long glass tube" },
  gas_syringe: { emoji: "💉", label: "Gas syringe" },
  funnel: { emoji: "🔻", label: "Funnel" },
  filter_paper: { emoji: "📄", label: "Filter paper" },
  glass_rod: { emoji: "🥢", label: "Glass rod" },
  thermometer: { emoji: "🌡️", label: "Thermometer" },
  fractionating_column: { emoji: "🏛️", label: "Fractionating column" },
  tweezers: { emoji: "🪡", label: "Tweezers" },
  pencil: { emoji: "✏️", label: "Pencil" },
  volumetric_flask: { emoji: "🍶", label: "Volumetric flask" },
  graduated_pipette: { emoji: "💉", label: "Graduated pipette" },
  pipette: { emoji: "💉", label: "Pipette" },
  evaporating_dish: { emoji: "🥣", label: "Evaporating dish" },
  sandpaper: { emoji: "🟫", label: "Sandpaper" },
  ice_cubes: { emoji: "🧊", label: "Ice cubes" },
  sugar: { emoji: "🍬", label: "Sugar" },
  copper_oxide: { emoji: "⚫", label: "Copper(II) oxide" },
  magnesium: { emoji: "🎗️", label: "Magnesium" },
  iron: { emoji: "🔩", label: "Iron" },
  conc_hcl: { emoji: "☠️", label: "Concentrated HCl" },
  cotton_nh3: { emoji: "☁️", label: "Cotton with ammonia" },
  cotton_hcl: { emoji: "☁️", label: "Cotton with HCl" },
  glowing_splint: { emoji: "🪵", label: "Glowing splint" },
  burning_splint: { emoji: "🔥", label: "Burning splint" },
  // solids
  anhydrous_cuso4: { emoji: "⚪", label: "Anhydrous copper sulphate" },
  hydrated_cuso4: { emoji: "🔷", label: "Hydrated copper sulphate" },
  sodium_chloride: { emoji: "🧂", label: "Sodium chloride" },
  sodium_carbonate: { emoji: "🧂", label: "Sodium carbonate" },
  copper: { emoji: "🟤", label: "Copper" },
  zinc: { emoji: "⚙️", label: "Zinc" },
  limestone: { emoji: "🪨", label: "Limestone chips" },
  sand: { emoji: "🏖️", label: "Sand" },
  butter: { emoji: "🧈", label: "Butter" },
  aluminium_powder: { emoji: "✨", label: "Aluminium powder" },
  // indicators and test papers
  red_litmus: { emoji: "🟥", label: "Red litmus" },
  blue_litmus: { emoji: "🟦", label: "Blue litmus" },
  damp_red_litmus: { emoji: "🟥", label: "Damp red litmus" },
  damp_blue_litmus: { emoji: "🟦", label: "Damp blue litmus" },
  methyl_orange: { emoji: "🟠", label: "Methyl orange" },
  phenolphthalein: { emoji: "🩷", label: "Phenolphthalein" },
  starch: { emoji: "🍚", label: "Starch solution" },
  // liquids and solutions
  water: { emoji: "💧", label: "Water" },
  distilled_water: { emoji: "💧", label: "Distilled water" },
  lime_water: { emoji: "🥛", label: "Lime water" },
  naoh: { emoji: "🧴", label: "Sodium hydroxide" },
  dilute_hcl: { emoji: "🧴", label: "Dilute HCl" },
  dilute_h2so4: { emoji: "🧴", label: "Dilute sulphuric acid" },
  h2o2: { emoji: "🫧", label: "Hydrogen peroxide" },
  acidified_kmno4: { emoji: "🟣", label: "Acidified KMnO₄" },
  kmno4: { emoji: "🟣", label: "Potassium permanganate" },
  vegetable_oil: { emoji: "🫒", label: "Vegetable oil" },
  ethanol_water: { emoji: "🍶", label: "Ethanol–water mixture" },
  silver_nitrate: { emoji: "🥈", label: "Silver nitrate" },
  barium_nitrate: { emoji: "🧴", label: "Barium nitrate" },
  aqueous_ammonia: { emoji: "🧴", label: "Aqueous ammonia" },
  acetic_anhydride: { emoji: "🧴", label: "Acetic anhydride" },
  mnso4: { emoji: "🧴", label: "Manganese(II) sulphate" },
  alkaline_iodide_azide: { emoji: "🧴", label: "Alkaline iodide azide" },
  dnph: { emoji: "🟠", label: "2,4-DNPH" },
  fehling_solution: { emoji: "🔵", label: "Fehling's solution" },
  tollens_reagent: { emoji: "🪞", label: "Tollens' reagent" },
  ink_sample: { emoji: "🖋️", label: "Ink" },
  ink_crime_scene: { emoji: "📝", label: "Crime scene ink" },
  ink_suspect_a: { emoji: "🖊️", label: "Suspect A's pen" },
  ink_suspect_b: { emoji: "🖊️", label: "Suspect B's pen" },
};

export function item(id: string): { emoji: string; label: string } {
  return ITEMS[id] ?? { emoji: "🧪", label: words(id) };
}

/** Names for drop targets and zones; anything unlisted is turned into words. */
const PLACES: Record<string, string> = {
  stand: "Clamp stand", under: "Under the burette", in_funnel: "Inside the funnel", on_flask: "On the flask",
  column_top: "Top of the column", left_end: "Left end", right_end: "Right end", tube_a: "Tube A", tube_b: "Tube B",
  lane_1: "Lane 1", lane_2: "Lane 2", lane_3: "Lane 3", first: "Add first", second: "Add second",
  chromatography_paper: "Chromatography paper", sodium_metal: "Sodium", gas_tube: "Gas", tray: "Tray",
  tripod: "Tripod stand", volumetric_flask: "Volumetric flask", conc_hcl: "Concentrated HCl",
  tube_mg: "Tube: Mg", tube_zn: "Tube: Zn", tube_fe: "Tube: Fe", tube_cu: "Tube: Cu",
};

export function place(id: string): string {
  return PLACES[id] ?? words(id);
}

function words(id: string): string {
  const s = id.replace(/_/g, " ");
  return s.charAt(0).toUpperCase() + s.slice(1);
}

/** Colours for add_until's visual change, in the prototype's palette. */
export const COLOURS: Record<string, string> = {
  pink: "#F48FB1", colourless: "#F5FBFF", pale_green: "#C5E8C9", pale_purple: "#CE93D8",
  yellow_brown: "#C9A227", pale_yellow: "#FFF59D", blue: "#4FC3F7", yellow: "#FFD54F", orange: "#FF8A50",
  bubbling: "#E8F4FD", fizzing: "#E8F4FD", zinc_settles: "#CFD8DC", solid_left: "#ECEFF1",
};

export function colour(name: string): string {
  return COLOURS[name] ?? "#E8F4FD";
}
