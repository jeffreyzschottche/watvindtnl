export interface IssueSource {
  kind: "editorial" | "news" | "motion" | "legacy";
  name: string | null;
  title: string | null;
  url: string | null;
  published_at: string | null;
  verified_at: string | null;
}

import type { ReportCounts } from "~/types/reports";

export interface IssueNewsLink {
  id: number;
  slug: string;
  title: string;
  excerpt: string | null;
  generated_at: string | null;
}

export interface IssueArgument {
  id: number;
  body: string;
  sources: string[];
  source_reports: ReportCounts;
  created_at: string | null;
  updated_at: string | null;
}

export interface IssueArgumentsGroup {
  pro: IssueArgument[];
  con: IssueArgument[];
}

export interface IssuePartyStanceParty {
  id: number;
  name: string;
  abbreviation: string;
  slug: string;
  logo_url: string | null;
  website_url: string | null;
}

export interface IssuePartyStances {
  agree: IssuePartyStanceParty[];
  disagree: IssuePartyStanceParty[];
  neutral: IssuePartyStanceParty[];
}

export interface IssueWithArguments {
  id: number;
  title: string;
  slug: string;
  url: string | null;
  description: string | null;
  more_info: string | null;
  source?: IssueSource;
  news_article_slug?: string | null;
  news_article?: IssueNewsLink | null;
  party_stances: IssuePartyStances;
  reports: ReportCounts;
  votes: IssueVoteCounts | null;
  user_vote: IssueVoteOption | null;
  arguments: IssueArgumentsGroup;
  created_at: string | null;
  updated_at: string | null;
}

export interface IssueVoteCounts {
  agree: number;
  disagree: number;
  neutral: number;
}
export type NewIssueVoteOption = "agree" | "disagree";

export type IssueVoteOption = "agree" | "disagree" | "neutral";

export interface IssueVoteResponse {
  status: string;
  vote: IssueVoteOption;
  voted_issue_ids: number[];
  votes: IssueVoteCounts;
  user_vote: IssueVoteOption;
}

export interface UserVoteHistoryItem {
  id: number;
  title: string;
  slug: string;
  description: string | null;
  vote: IssueVoteOption;
  created_at: string | null;
  updated_at: string | null;
}
