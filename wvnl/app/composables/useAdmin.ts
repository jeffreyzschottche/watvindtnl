import { computed, ref } from "vue";
import { useAuth } from "~/composables/useAuth";
import { createAdminService } from "~/services/admin";
import type {
  AdminArgument,
  AdminArgumentPayload,
  AdminIssue,
  AdminIssuePayload,
  AdminPoliticalParty,
  AdminPoliticalPartyPayload,
} from "~/types/admin";

const ADMIN_EMAIL = "jeffreyjeffreyzschot@gmail.com";
const ADMIN_USERNAME = "jeffreyzschot";

export function useAdmin() {
  const auth = useAuth();
  const service = createAdminService();

  const issues = ref<AdminIssue[]>([]);
  const reports = ref<AdminIssue[]>([]);
  const parties = ref<AdminPoliticalParty[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);

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

  async function withErrorHandling<T>(callback: () => Promise<T>): Promise<T | null> {
    try {
      setError(null);
      loading.value = true;
      const result = await callback();
      return result;
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : "Er is iets misgegaan.");
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
      issues.value = [created, ...issues.value];
      reports.value = [created, ...reports.value];
      return created;
    });
    return issue;
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
      const target = argument.side === "pro" ? issue.arguments.pro : issue.arguments.con;
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
    deleteIssue,
    addArgument,
    removeArgument,
    createParty,
    updateParty,
    auth,
    ADMIN_EMAIL,
    ADMIN_USERNAME,
  };
}
