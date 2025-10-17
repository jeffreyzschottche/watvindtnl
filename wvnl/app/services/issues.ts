import { apiFetch } from "~/services/apiFetch";
import type {
  IssueVoteOption,
  IssueVoteResponse,
  IssueWithArguments,
} from "~/types/issues";
import type { ReportCounts, ReportReason } from "~/types/reports";

interface IssueListParams {
  limit?: number;
  offset?: number;
}

interface IssueReportResponse {
  status: string;
  reports: ReportCounts;
}

interface ArgumentReportResponse {
  status: string;
  source_reports: ReportCounts;
}

function buildHeaders(token?: string, extra?: HeadersInit): Headers {
  const headers = new Headers(extra ?? {});
  if (!headers.has("Accept")) headers.set("Accept", "application/json");
  if (!headers.has("X-Requested-With"))
    headers.set("X-Requested-With", "XMLHttpRequest");
  if (token && !headers.has("Authorization"))
    headers.set("Authorization", `Bearer ${token}`);
  return headers;
}

function buildListQuery(params?: IssueListParams): string {
  const searchParams = new URLSearchParams();
  if (params?.limit !== undefined) {
    searchParams.set("limit", String(params.limit));
  }
  if (params?.offset !== undefined) {
    searchParams.set("offset", String(params.offset));
  }

  const query = searchParams.toString();
  return query ? `?${query}` : "";
}

export function fetchPendingIssues(
  token?: string,
  params?: IssueListParams,
  init?: RequestInit
): Promise<IssueWithArguments[]> {
  const query = buildListQuery(params);
  return apiFetch<IssueWithArguments[]>(`/issues/pending${query}`, {
    ...init,
    headers: buildHeaders(token, init?.headers ?? undefined),
  });
}

export function fetchIssuesWithArguments(
  params?: IssueListParams,
  init?: RequestInit
): Promise<IssueWithArguments[]> {
  const query = buildListQuery(params);
  return apiFetch<IssueWithArguments[]>(`/issues-args${query}`, init);
}

export function fetchIssueWithArguments(
  issueId: number,
  token?: string,
  init?: RequestInit
): Promise<IssueWithArguments> {
  return apiFetch<IssueWithArguments>(`/issues-args/${issueId}`, {
    ...init,
    headers: buildHeaders(token, init?.headers ?? undefined),
  });
}

export function voteOnIssue(
  issueId: number,
  vote: IssueVoteOption,
  token?: string,
  init?: RequestInit
): Promise<IssueVoteResponse> {
  const headers = buildHeaders(token, init?.headers ?? undefined);
  if (!headers.has("Content-Type"))
    headers.set("Content-Type", "application/json");

  return apiFetch<IssueVoteResponse>(`/issues/${issueId}/vote`, {
    method: "POST",
    ...init,
    headers,
    body: JSON.stringify({ vote }),
  });
}

export function reportIssue(
  issueId: number,
  reason: ReportReason,
  token?: string,
  init?: RequestInit
): Promise<IssueReportResponse> {
  const headers = buildHeaders(token, init?.headers ?? undefined);
  if (!headers.has("Content-Type"))
    headers.set("Content-Type", "application/json");

  return apiFetch<IssueReportResponse>(`/issues/${issueId}/report`, {
    method: "POST",
    ...init,
    headers,
    body: JSON.stringify({ reason }),
  });
}

export function reportArgument(
  argumentId: number,
  reason: ReportReason,
  token?: string,
  init?: RequestInit
): Promise<ArgumentReportResponse> {
  const headers = buildHeaders(token, init?.headers ?? undefined);
  if (!headers.has("Content-Type"))
    headers.set("Content-Type", "application/json");

  return apiFetch<ArgumentReportResponse>(`/arguments/${argumentId}/report`, {
    method: "POST",
    ...init,
    headers,
    body: JSON.stringify({ reason }),
  });
}
