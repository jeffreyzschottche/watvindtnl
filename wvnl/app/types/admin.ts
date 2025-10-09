import type { ReportCounts } from "~/types/reports";

export interface AdminPartyStances {
  agree: number[];
  neutral: number[];
  disagree: number[];
}

export interface AdminVotes {
  agree: number[];
  disagree: number[];
  neutral: number[];
}

export interface AdminArgument {
  id: number;
  issue_id: number;
  side: "pro" | "con";
  body: string;
  sources: string[];
  source_reports: unknown;
  created_at: string | null;
  updated_at: string | null;
}

export interface AdminIssueArguments {
  pro: AdminArgument[];
  con: AdminArgument[];
}

export interface AdminIssue {
  id: number;
  title: string;
  slug: string;
  url: string | null;
  description: string | null;
  more_info: string | null;
  party_stances: AdminPartyStances;
  reports: ReportCounts;
  votes: AdminVotes;
  arguments: AdminIssueArguments;
  created_at: string | null;
  updated_at: string | null;
}

export interface AdminIssuePayload {
  title: string;
  slug?: string;
  url?: string | null;
  description?: string | null;
  more_info?: string | null;
  party_stances: AdminPartyStances;
  reports?: ReportCounts;
  votes?: AdminVotes;
  arguments?: {
    pro?: AdminArgumentPayload[];
    con?: AdminArgumentPayload[];
  };
}

export interface AdminArgumentPayload {
  side: "pro" | "con";
  body: string;
  sources?: string[];
}

export interface AdminPoliticalParty {
  id: number;
  name: string;
  abbreviation: string;
  slug: string;
  logo_url: string | null;
  website_url: string | null;
  created_at: string | null;
  updated_at: string | null;
}

export interface AdminPoliticalPartyPayload {
  name: string;
  abbreviation: string;
  slug?: string;
  logo_url?: string | null;
  website_url?: string | null;
}
