import example from "~~/scripts/design/compass-poster-example.json";
import type {
  CompassDimension,
  CompassExample,
  VoteReport,
} from "~/types/voteReports";

const { snapshot: sample } = example;
const answers = 12;
// Separate fictional economic scores, independent of the four themes that define a type.
const economyByType: Record<string, number> = {
  "sociale-vernieuwer": 65,
  "groene-vernieuwer": 61,
  "internationale-verbinder": 58,
  "praktische-hervormer": 53,
  "sociale-beschermer": 70,
  "zorgzame-behouder": 63,
  "lokale-verbinder": 55,
  "nationale-beschermer": 48,
  "vrije-denker": 45,
  vrijheidsbewaker: 35,
  "ondernemende-vernieuwer": 31,
  "zelfstandige-bouwer": 39,
  "groene-pragmaticus": 56,
  "traditionele-bouwer": 42,
  "internationale-ondernemer": 37,
  "betrokken-middenkoers": 50,
};

const interpretations: Record<string, [string, string, string]> = {
  social: [
    "Gezamenlijke voorzieningen wegen zwaarder in keuzes over zorg en zekerheid.",
    "Bij zorg en zekerheid ligt het accent op keuzevrijheid en eigen verantwoordelijkheid.",
    "Bij zorg en zekerheid krijgen gezamenlijke voorzieningen en individuele keuzevrijheid ongeveer evenveel gewicht.",
  ],
  progressive: [
    "Er is meer ruimte voor nieuwe oplossingen als bestaande regels knellen.",
    "Bij maatschappelijke veranderingen wegen vertrouwde regels en continuïteit zwaarder.",
    "De keuzes houden ruimte voor vernieuwing én voor wat al werkt.",
  ],
  international: [
    "Voor grensoverschrijdende problemen ligt de nadruk op afspraken met andere landen.",
    "Voor grensoverschrijdende problemen ligt de nadruk op nationale zeggenschap.",
    "Samenwerking met andere landen en nationale zeggenschap krijgen ongeveer evenveel gewicht.",
  ],
  green: [
    "Bij keuzes voor de lange termijn krijgen natuur en duurzaamheid meer gewicht.",
    "Bij keuzes voor de lange termijn krijgen economische groei en bedrijvigheid meer ruimte.",
    "Bij keuzes voor de lange termijn tellen duurzaamheid en economische groei ongeveer even zwaar mee.",
  ],
};

export function compassExampleReport(
  selected: CompassExample,
  catalogue: CompassExample[],
): VoteReport {
  const economicScore = economyByType[selected.id] ?? 50;
  const dimensions: CompassDimension[] = selected.dimensions.map(
    (dimension) => {
      const definition = sample.compass.dimensions.find(
        ({ left }) => left === dimension.left,
      );
      return {
        ...dimension,
        id: definition?.id ?? dimension.left,
        icon: definition?.icon ?? "balance",
        opposite_percentage: 100 - dimension.percentage,
        compared: answers,
        neutral: 0,
        minimum: 5,
        sensitivity: null,
      };
    },
  );
  const traits = [...dimensions]
    .filter(({ percentage }) => percentage !== 50)
    .sort((a, b) => Math.abs(b.percentage! - 50) - Math.abs(a.percentage! - 50))
    .slice(0, 3)
    .map(({ left, right, percentage }) => (percentage! > 50 ? left : right));
  const highlights = dimensions.flatMap(({ id, percentage }) => {
    const copy = interpretations[id];
    if (!copy || percentage === null) return [];
    return [
      {
        text: copy[percentage === 50 ? 2 : percentage > 50 ? 0 : 1],
        evidence_ids: [],
      },
    ];
  });

  return {
    id: `example-${selected.id}`,
    status: "ready",
    amount_cents: 0,
    currency: "EUR",
    created_at: sample.generated_at,
    ready_at: sample.generated_at,
    vote_count: answers,
    checkout_url: null,
    download_available: false,
    snapshot: {
      total: answers,
      counts: { agree: 8, disagree: 4, neutral: 0 },
      entries: [],
      party_matches: sample.party_matches,
      methodology_version: sample.methodology_version,
      generated_at: sample.generated_at,
      limits: [],
      compass: {
        version: sample.compass.version,
        status: "ready",
        type: {
          ...selected,
          provisional: false,
          traits: traits.length ? traits : ["Afgewogen keuzes"],
        },
        type_catalogue: catalogue,
        dimensions,
        spectrum: {
          id: "economic",
          left: "Links",
          right: "Rechts",
          icon: "balance",
          compared: answers,
          minimum: 5,
          neutral: 0,
          percentage: economicScore,
          opposite_percentage: 100 - economicScore,
          sensitivity: null,
        },
        score_adjustment: {
          ...sample.compass.score_adjustment,
          display_range: [1, 99],
        },
        used_votes: answers,
        minimum_per_dimension: 5,
        highlights: [],
      },
    },
    narrative: {
      summary: selected.description,
      reflection: "",
      analysis_origin: "code",
      analysis_version: 4,
      highlights,
    },
  };
}
