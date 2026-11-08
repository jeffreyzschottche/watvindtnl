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
export type CompassDimension = {
  id: string;
  left: string;
  right: string;
  icon: string;
  percentage: number | null;
  opposite_percentage: number | null;
  compared: number;
  neutral: number;
  minimum: number;
  sensitivity: { min: number; max: number } | null;
};
export type CompassAnalysis = {
  version: string;
  status: "ready" | "insufficient_data";
  type: { id: string; name: string; icon: string; traits: string[] } | null;
  dimensions: CompassDimension[];
  spectrum: CompassDimension;
  used_votes: number;
  minimum_per_dimension: number;
  highlights: string[];
};
export type ReportSnapshot = {
  compass?: CompassAnalysis;
  total: number;
  counts: Record<ReportEntry["choice"], number>;
  entries: ReportEntry[];
  party_matches: {
    name: string;
    percentage: number;
    matched: number;
    compared: number;
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
  narrative?: { summary: string; reflection: string };
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
};
