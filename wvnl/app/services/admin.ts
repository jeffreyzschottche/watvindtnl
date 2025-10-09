import { useApi } from "~/composables/useApi";
import type {
  AdminArgument,
  AdminArgumentPayload,
  AdminArgumentImportPayload,
  AdminArgumentUpsertPayload,
  AdminIssue,
  AdminIssuePayload,
  AdminIssueImportPayload,
  AdminPoliticalParty,
  AdminPoliticalPartyPayload,
} from "~/types/admin";

export type AdminApiClient = ReturnType<typeof useApi>;

export function createAdminService(api: AdminApiClient = useApi()) {
  return {
    fetchIssues: () => api.get<AdminIssue[]>("/admin/issues"),
    fetchReports: () => api.get<AdminIssue[]>("/admin/reports"),
    createIssue: (payload: AdminIssuePayload) =>
      api.post<AdminIssue>("/admin/issues", payload),
    updateIssue: (issueId: number, payload: AdminIssuePayload) =>
      api.patch<AdminIssue>(`/admin/issues/${issueId}`, payload),
    importIssues: (payload: AdminIssueImportPayload) =>
      api.post<AdminIssue[]>("/admin/issues/import", payload),
    deleteIssue: (issueId: number) => api.del(`/admin/issues/${issueId}`),
    createArgument: (issueId: number, payload: AdminArgumentPayload) =>
      api.post<AdminArgument>(`/admin/issues/${issueId}/arguments`, payload),
    updateArgument: (
      argumentId: number,
      payload: AdminArgumentUpsertPayload
    ) => api.patch<AdminArgument>(`/admin/arguments/${argumentId}`, payload),
    importArguments: (payload: AdminArgumentImportPayload) =>
      api.post<AdminArgument[]>("/admin/arguments/bulk", payload),
    deleteArgument: (argumentId: number) =>
      api.del(`/admin/arguments/${argumentId}`),
    fetchPoliticalParties: () =>
      api.get<AdminPoliticalParty[]>("/admin/political-parties"),
    createPoliticalParty: (payload: AdminPoliticalPartyPayload) =>
      api.post<AdminPoliticalParty>("/admin/political-parties", payload),
    updatePoliticalParty: (
      partyId: number,
      payload: Partial<AdminPoliticalPartyPayload>
    ) => api.patch<AdminPoliticalParty>(`/admin/political-parties/${partyId}`, payload),
  };
}
