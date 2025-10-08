import { apiFetch } from "~/services/apiFetch";
import type {
  IssueVoteOption,
  IssueVoteResponse,
  IssueWithArguments,
} from "~/types/issues";
import type { ReportCounts, ReportReason } from "~/types/reports";

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

export function fetchPendingIssues(
  token?: string,
  init?: RequestInit
): Promise<IssueWithArguments[]> {
  return apiFetch<IssueWithArguments[]>("/issues/pending", {
    ...init,
    headers: buildHeaders(token, init?.headers ?? undefined),
  });
}

export function fetchIssuesWithArguments(
  init?: RequestInit
): Promise<IssueWithArguments[]> {
  return apiFetch<IssueWithArguments[]>("/issues-args", init);
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
