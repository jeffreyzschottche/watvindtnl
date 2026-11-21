export type ReportProduct = {
  price_cents: number;
  currency: string;
  purchase_type: string;
  minimum_votes: number;
  checkout_available: boolean;
  native_purchase_available: boolean;
  ai_available: boolean;
  ai_provider: string;
  methodology_version: string;
};
export type ReportEntry = {
  id: number;
  slug: string;
  title: string;
  choice: "agree" | "disagree" | "neutral";
};
export type ScoreAdjustment = {
  version: string;
  neutral_anchor: number;
  display_range: [number, number];
};
export type CompassDimension = {
  id: string;
  left: string;
  right: string;
  icon: string;
  provisional?: boolean;
  historical?: number;
  early_minimum?: number;
  percentage: number | null;
  observed_percentage?: number | null;
  opposite_percentage: number | null;
  compared: number;
  neutral: number;
  minimum: number;
  sensitivity: { min: number; max: number } | null;
};
type CompassTypeSummary = {
  id: string;
  name: string;
  color: string;
  icon: string;
  description: string;
};
export type CompassExample = CompassTypeSummary & {
  dimensions: { left: string; right: string; percentage: number }[];
};
export type CompassAnalysis = {
  version: string;
  score_adjustment?: ScoreAdjustment;
  status: "ready" | "provisional" | "insufficient_data";
  type: {
    id: string;
    name: string;
    color?: string;
    description?: string;
    icon: string;
    traits: string[];
    provisional?: boolean;
    based_on_dimensions?: string[];
  } | null;
  type_catalogue?: CompassTypeSummary[];
  dimensions: CompassDimension[];
  spectrum: CompassDimension;
  used_votes: number;
  historical_votes?: number;
  unique_answers?: number;
  duplicate_answers?: number;
  conflicting_groups?: number;
  topics?: {
    name: string;
    count: number;
    agree: number;
    disagree: number;
    neutral: number;
  }[];
  minimum_per_dimension: number;
  highlights: string[];
};
export type ReportInsight = {
  heading: string;
  body: string;
  evidence_ids: number[];
};
export type ReportNarrative = {
  summary: string;
  reflection: string;
  analysis_version?: number;
  analysis_origin?: "ai" | "code";
  highlights?: { text: string; evidence_ids: number[] }[];
  insights?: ReportInsight[];
  positions?: { statement_id: number; interpretation: string }[];
  nuances?: ReportInsight[];
  questions?: { text: string; evidence_ids: number[] }[];
};
export type ReportSnapshot = {
  analysis_context?: {
    version: number;
    votes: (ReportEntry & {
      description?: string;
      context?: string;
      topics?: string[];
      historical?: boolean;
      source_date?: string | null;
    })[];
    eligible_count: number;
    included_count: number;
    omitted_count: number;
    excluded_conflicts: number;
  };
  compass?: CompassAnalysis;
  total: number;
  counts: Record<ReportEntry["choice"], number>;
  entries: ReportEntry[];
  party_coverage?: {
    score_adjustment?: ScoreAdjustment;
    verified_motions: number;
    minimum: number;
    early_minimum: number;
  };
  party_matches: {
    name: string;
    percentage: number;
    balanced_score?: number;
    matched: number;
    compared: number;
    provisional?: boolean;
  }[];
  methodology_version: string;
  generated_at: string;
  limits: string[];
};
export type VoteReport = {
  is_development?: boolean;
  generation_model?: string;
  generation_provider?: string;
  generation_usage?: {
    input_tokens: number;
    output_tokens: number;
    estimated_cost_eur: number;
  };
  expires_at?: string;
  id: string;
  status:
    | "pending"
    | "paid"
    | "generating"
    | "ready"
    | "failed"
    | "canceled"
    | "refunded";
  amount_cents: number;
  currency: string;
  created_at: string;
  ready_at: string | null;
  vote_count: number;
  checkout_url: string | null;
  download_available: boolean;
  snapshot?: ReportSnapshot;
  narrative?: ReportNarrative;
  narrative_source?: string;
};
export type ReportOverview = {
  compass_progress?: {
    ready: boolean;
    used_votes: number;
    dimensions: { name: string; compared: number; minimum: number }[];
  };
  product: ReportProduct;
  vote_count: number;
  comparison: {
    previous_report_at: string | null;
    new_votes: number;
    suggest_update: boolean;
    message: string | null;
  };
  orders: VoteReport[];
  orders_next_page?: number | null;
};
