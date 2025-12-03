export interface NewsArticleSection {
  heading: string;
  content: string;
}

export interface NewsArticleSummary {
  id: number;
  issue_id: number;
  slug: string;
  title: string;
  subtitle: string | null;
  excerpt: string | null;
  body: string;
  sections: NewsArticleSection[];
  call_to_action: string | null;
  generated_at: string | null;
  updated_at: string | null;
  issue: {
    id: number;
    title: string;
    slug: string;
  } | null;
}

export interface NewsArticleListItem
  extends Pick<NewsArticleSummary, "id" | "slug" | "issue_id" | "title" | "subtitle" | "excerpt" | "sections" | "call_to_action" | "generated_at"> {
  issue: NewsArticleSummary["issue"];
}
