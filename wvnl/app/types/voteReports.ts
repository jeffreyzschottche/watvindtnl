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
export type ReportSnapshot = {
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
