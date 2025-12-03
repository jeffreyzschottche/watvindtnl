import { apiFetch } from "~/services/apiFetch";
import type { NewsArticleListItem, NewsArticleSummary } from "~/types/news";

interface ListParams {
  search?: string;
  limit?: number;
}

function buildQuery(params?: ListParams): string {
  const query = new URLSearchParams();
  if (params?.search) {
    query.set("search", params.search);
  }
  if (params?.limit) {
    query.set("limit", String(params.limit));
  }
  const result = query.toString();
  return result ? `?${result}` : "";
}

export function fetchNewsArticles(params?: ListParams): Promise<NewsArticleListItem[]> {
  const query = buildQuery(params);
  return apiFetch<NewsArticleListItem[]>(`/news-articles${query}`);
}

export function fetchNewsArticle(slug: string): Promise<NewsArticleSummary> {
  return apiFetch<NewsArticleSummary>(`/news-articles/${slug}`);
}

export function fetchNewsArticleForIssue(issueId: number): Promise<NewsArticleSummary> {
  return apiFetch<NewsArticleSummary>(`/issues/${issueId}/news-article`);
}
