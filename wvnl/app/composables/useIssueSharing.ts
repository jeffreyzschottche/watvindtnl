import { storeToRefs } from "pinia";
import { useRoute, useRouter } from "#imports";
import { useNotificationStore } from "~/stores/notifications";
import { useAuthStore } from "~/stores/auth";
import type { IssueVoteOption, IssueWithArguments } from "~/types/issues";

export type ShareableIssue = Pick<IssueWithArguments, "id" | "title"> & {
  votes?: IssueWithArguments["votes"];
};

export interface ShareIssueOptions {
  vote?: IssueVoteOption | null;
  path?: string;
}

export function useIssueSharing() {
  const router = useRouter();
  const route = useRoute();
  const notifications = useNotificationStore();
  const auth = useAuthStore();
  const { isLoggedIn, user } = storeToRefs(auth);

  async function shareIssue(
    issue: ShareableIssue,
    options: ShareIssueOptions = {}
  ): Promise<boolean> {
    const link = buildShareLink(issue.id, options.path);
    const vote = resolveVote(issue, options.vote);
    const message = composeShareMessage(issue.title, vote, link);

    try {
      await copyToClipboard(message);
      notifications.addNotification({
        message: "Gekopieerd naar klembord.",
        type: "success",
      });
      return true;
    } catch (error) {
      notifications.addNotification({
        message: "Kopiëren is niet gelukt.",
        type: "error",
      });
      return false;
    }
  }

  function buildShareLink(issueId: number, path?: string) {
    const targetPath = path ?? route?.path ?? "/issues";
    const resolved = router.resolve({
      path: targetPath,
      query: { issue_id: issueId },
    });

    if (typeof window !== "undefined") {
      return new URL(resolved.href, window.location.origin).toString();
    }

    return resolved.href;
  }

  function resolveVote(
    issue: ShareableIssue,
    explicit?: IssueVoteOption | null
  ): IssueVoteOption | null {
    if (explicit !== undefined) {
      return explicit;
    }

    const currentUserId = user.value?.id;
    if (!currentUserId || !issue.votes) {
      return null;
    }

    const { agree = [], disagree = [], neutral = [] } = issue.votes;

    if (agree.includes(currentUserId)) return "agree";
    if (disagree.includes(currentUserId)) return "disagree";
    if (neutral.includes(currentUserId)) return "neutral";

    return null;
  }

  function composeShareMessage(
    title: string,
    vote: IssueVoteOption | null,
    link: string
  ) {
    if (!isLoggedIn.value || !vote) {
      return `Deel je mening over ${title}. ${link}`;
    }

    if (vote === "agree") {
      return `Ik ben het eens met ${title}. Wat vind jij? ${link}`;
    }

    if (vote === "disagree") {
      return `Ik ben het oneens met ${title}. Wat vind jij? ${link}`;
    }

    return `Ik ben neutraal over ${title}. Wat vind jij? ${link}`;
  }

  async function copyToClipboard(text: string) {
    if (typeof navigator !== "undefined" && navigator.clipboard?.writeText) {
      await navigator.clipboard.writeText(text);
      return;
    }

    if (typeof document === "undefined") {
      throw new Error("Clipboard niet beschikbaar");
    }

    const textarea = document.createElement("textarea");
    textarea.value = text;
    textarea.setAttribute("readonly", "");
    textarea.style.position = "absolute";
    textarea.style.left = "-9999px";
    document.body.appendChild(textarea);
    textarea.select();
    const successful = document.execCommand("copy");
    document.body.removeChild(textarea);

    if (!successful) {
      throw new Error("Clipboard niet beschikbaar");
    }
  }

  return { shareIssue };
}
