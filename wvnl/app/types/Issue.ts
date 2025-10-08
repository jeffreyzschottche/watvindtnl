export interface IssueArgument {
  id: number;
  body: string;
  sources?: string[];
  source_reports?: string[];
  created_at?: string | null;
  updated_at?: string | null;
}

export interface IssueVotes {
  agree: number[];
  disagree: number[];
  neutral: number[];
}

export interface Issue {
  id: number;
  title: string;
  slug: string;
  url?: string | null;
  description?: string | null;
  more_info?: string | null;
  party_stances?: Record<string, unknown>;
  votes?: IssueVotes;
  arguments?: {
    pro: IssueArgument[];
    con: IssueArgument[];
  };
  created_at?: string;
  updated_at?: string;
}
