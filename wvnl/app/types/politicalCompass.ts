export interface PoliticalCompassLabel {
  term: string;
  hoofdkenmerk: string | null;
  spectrum: string | null;
}

export interface PoliticalCompassParty {
  id: number;
  name: string;
  abbreviation: string;
  slug: string;
  logo_url: string | null;
  website_url: string | null;
  motivatie: string;
}

export interface PoliticalCompassEntry {
  id: number;
  stemgedrag_score: number;
  politieke_label: PoliticalCompassLabel;
  aanbevolen_partij: PoliticalCompassParty | null;
  created_at: string | null;
  updated_at: string | null;
}

export interface PoliticalCompassOverview {
  latest: PoliticalCompassEntry | null;
  history: PoliticalCompassEntry[];
  can_generate: boolean;
  next_available_at: string | null;
  minimum_votes: number;
  vote_count: number;
}
