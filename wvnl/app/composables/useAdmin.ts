import { computed, ref } from "vue";
import { useAuth } from "~/composables/useAuth";
import { createAdminService } from "~/services/admin";
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
import { translateErrorMessage } from "~/utils/translateErrorMessage";

const ADMIN_EMAIL = "jeffreyzschot@gmail.com";
const ADMIN_USERNAME = "jeffreyzschot";

export function useAdmin() {
  const auth = useAuth();
  const service = createAdminService();

  const issues = ref<AdminIssue[]>([]);
  const reports = ref<AdminIssue[]>([]);
  const parties = ref<AdminPoliticalParty[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);

  function sortIssues(list: AdminIssue[]): AdminIssue[] {
    return [...list].sort((a, b) => {
      const aDate = a.created_at ? new Date(a.created_at).getTime() : 0;
      const bDate = b.created_at ? new Date(b.created_at).getTime() : 0;
      return bDate - aDate;
    });
  }

  function sortArguments(list: AdminArgument[]): AdminArgument[] {
    return [...list].sort((a, b) => {
      const aDate = a.created_at ? new Date(a.created_at).getTime() : 0;
      const bDate = b.created_at ? new Date(b.created_at).getTime() : 0;
      return aDate - bDate;
    });
  }

  function upsertIssue(issue: AdminIssue) {
    const updateList = (collection: AdminIssue[]) => {
      const filtered = collection.filter((item) => item.id !== issue.id);
      return sortIssues([issue, ...filtered]);
    };

    issues.value = updateList(issues.value);
    reports.value = updateList(reports.value);
  }

  const isAdmin = computed(() => {
    const current = auth.user.value;
    return (
      !!current &&
      current.email === ADMIN_EMAIL &&
      current.username === ADMIN_USERNAME
    );
  });

  function setError(message: string | null) {
    error.value = message;
  }

  async function withErrorHandling<T>(
    callback: () => Promise<T>
  ): Promise<T | null> {
    try {
      setError(null);
      loading.value = true;
      const result = await callback();
      return result;
    } catch (err: unknown) {
      setError(
        translateErrorMessage(err, { fallback: "Er is iets misgegaan." })
      );
      return null;
    } finally {
      loading.value = false;
    }
  }

  async function loadIssues() {
    await withErrorHandling(async () => {
      const data = await service.fetchIssues();
      issues.value = data;
      return data;
    });
  }

  async function loadReports() {
    await withErrorHandling(async () => {
      const data = await service.fetchReports();
      reports.value = data;
      return data;
    });
  }

  async function loadParties() {
    await withErrorHandling(async () => {
      const data = await service.fetchPoliticalParties();
      parties.value = data;
      return data;
    });
  }

  async function createIssue(payload: AdminIssuePayload) {
    const issue = await withErrorHandling(async () => {
      const created = await service.createIssue(payload);
      upsertIssue(created);
      return created;
    });
    return issue;
  }

  async function updateIssue(issueId: number, payload: AdminIssuePayload) {
    const issue = await withErrorHandling(async () => {
      const updated = await service.updateIssue(issueId, payload);
      upsertIssue(updated);
      return updated;
    });
    return issue;
  }

  async function importIssues(payload: AdminIssueImportPayload) {
    const imported = await withErrorHandling(async () => {
      const response = await service.importIssues(payload);
      response.forEach((issue) => upsertIssue(issue));
      return response;
    });
    return imported;
  }

  async function deleteIssue(issueId: number) {
    await withErrorHandling(async () => {
      await service.deleteIssue(issueId);
      issues.value = issues.value.filter((issue) => issue.id !== issueId);
      reports.value = reports.value.filter((issue) => issue.id !== issueId);
    });
  }

  async function addArgument(issueId: number, payload: AdminArgumentPayload) {
    const argument = await withErrorHandling(async () => {
      const created = await service.createArgument(issueId, payload);
      updateIssueArguments(created);
      return created;
    });
    return argument;
  }

  async function updateArgument(
    argumentId: number,
    payload: AdminArgumentUpsertPayload
  ) {
    const argument = await withErrorHandling(async () => {
      const updated = await service.updateArgument(argumentId, payload);
      updateIssueArguments(updated);
      return updated;
    });
    return argument;
  }

  async function importArguments(payload: AdminArgumentImportPayload) {
    const argumentsList = await withErrorHandling(async () => {
      const response = await service.importArguments(payload);
      response.forEach((argument) => updateIssueArguments(argument));
      return response;
    });
    return argumentsList;
  }

  async function removeArgument(argumentId: number) {
    await withErrorHandling(async () => {
      await service.deleteArgument(argumentId);
      for (const list of [issues.value, reports.value]) {
        list.forEach((issue) => {
          issue.arguments.pro = issue.arguments.pro.filter(
            (argument) => argument.id !== argumentId
          );
          issue.arguments.con = issue.arguments.con.filter(
            (argument) => argument.id !== argumentId
          );
        });
      }
    });
  }

  function updateIssueArguments(argument: AdminArgument) {
    for (const issue of [...issues.value, ...reports.value]) {
      if (issue.id !== argument.issue_id) continue;
      const target =
        argument.side === "pro" ? issue.arguments.pro : issue.arguments.con;
      const existingIndex = target.findIndex((item) => item.id === argument.id);
      if (existingIndex >= 0) {
        target.splice(existingIndex, 1, argument);
      } else {
        target.push(argument);
      }
    }
  }

  async function createParty(payload: AdminPoliticalPartyPayload) {
    const party = await withErrorHandling(async () => {
      const created = await service.createPoliticalParty(payload);
      parties.value = [created, ...parties.value];
      return created;
    });
    return party;
  }

  async function updateParty(
    partyId: number,
    payload: Partial<AdminPoliticalPartyPayload>
  ) {
    const party = await withErrorHandling(async () => {
      const updated = await service.updatePoliticalParty(partyId, payload);
      parties.value = parties.value.map((party) =>
        party.id === updated.id ? updated : party
      );
      return updated;
    });
    return party;
  }

  return {
    isAdmin,
    issues,
    reports,
    parties,
    loading,
    error,
    loadIssues,
    loadReports,
    loadParties,
    createIssue,
    updateIssue,
    importIssues,
    deleteIssue,
    addArgument,
    updateArgument,
    importArguments,
    removeArgument,
    createParty,
    updateParty,
    auth,
    ADMIN_EMAIL,
    ADMIN_USERNAME,
  };
}
