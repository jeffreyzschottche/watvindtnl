import { storeToRefs } from "pinia";
import { useRoute, useRouter } from "#imports";
import { useNotificationStore } from "~/stores/notifications";
import { useAuthStore } from "~/stores/auth";
import type { IssueVoteOption, IssueWithArguments } from "~/types/issues";

export type SharePlatform =
  | "instagram"
  | "facebook"
  | "twitter"
  | "whatsapp"
  | "clipboard";

export type ShareableIssue = Pick<IssueWithArguments, "id" | "title"> & {
  votes?: IssueWithArguments["votes"];
};

export interface ShareIssueOptions {
  vote?: IssueVoteOption | null;
  path?: string;
  platform?: SharePlatform;
}

const SHARE_IMAGE_URL =
  "https://via.placeholder.com/1200x630.png?text=Wat+vind+jij%3F";

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
    const platform = options.platform ?? "clipboard";

    if (platform !== "clipboard") {
      const messageWithImage = `${message}\n${SHARE_IMAGE_URL}`;
      const opened = openShareTarget(platform, {
        message: messageWithImage,
        link,
      });

      if (opened) {
        notifications.addNotification({
          message: shareSuccessMessage(platform),
          type: "success",
        });
        return true;
      }

      const fallbackCopied = await copyWithFeedback(
        messageWithImage,
        "We konden geen directe deling openen. De tekst is naar het klembord gekopieerd.",
        notifications,
        "info"
      );

      if (fallbackCopied) {
        return true;
      }

      notifications.addNotification({
        message: "Delen is niet gelukt.",
        type: "error",
      });
      return false;
    }

    return copyWithFeedback(message, "Gekopieerd naar klembord.", notifications);
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

  function openShareTarget(
    platform: SharePlatform,
    data: { message: string; link: string }
  ): boolean {
    if (typeof window === "undefined") {
      return false;
    }

    const url = buildShareUrl(platform, data);
    if (!url) {
      return false;
    }

    const opened = window.open(url, "_blank", "noopener,noreferrer");
    return opened !== null;
  }

  function buildShareUrl(
    platform: SharePlatform,
    data: { message: string; link: string }
  ): string | null {
    const encodedMessage = encodeURIComponent(data.message);
    const encodedLink = encodeURIComponent(data.link);

    switch (platform) {
      case "facebook":
        return `https://www.facebook.com/sharer/sharer.php?u=${encodedLink}&quote=${encodedMessage}&picture=${encodeURIComponent(
          SHARE_IMAGE_URL
        )}`;
      case "twitter":
        return `https://twitter.com/intent/tweet?text=${encodedMessage}`;
      case "whatsapp":
        return `https://api.whatsapp.com/send?text=${encodedMessage}`;
      case "instagram":
        return `https://www.instagram.com/?url=${encodedLink}`;
      default:
        return null;
    }
  }

  async function copyWithFeedback(
    text: string,
    successMessage: string,
    notificationsStore: ReturnType<typeof useNotificationStore>,
    type: "success" | "info" = "success"
  ): Promise<boolean> {
    try {
      await copyToClipboard(text);
      notificationsStore.addNotification({
        message: successMessage,
        type,
      });
      return true;
    } catch (error) {
      notificationsStore.addNotification({
        message: "Kopiëren is niet gelukt.",
        type: "error",
      });
      return false;
    }
  }

  function shareSuccessMessage(platform: SharePlatform) {
    switch (platform) {
      case "facebook":
        return "Facebook is geopend om je bericht te delen.";
      case "instagram":
        return "Instagram is geopend om je bericht te delen.";
      case "twitter":
        return "Twitter is geopend om je bericht te delen.";
      case "whatsapp":
        return "WhatsApp is geopend om je bericht te delen.";
      default:
        return "Deelvenster geopend.";
    }
  }

  return { shareIssue };
}
