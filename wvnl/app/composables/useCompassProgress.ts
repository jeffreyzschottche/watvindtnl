export type CompassProgress = {
  done: number;
  target: number;
  needed: number;
  used_votes: number;
  status: "ready" | "provisional" | "insufficient_data";
  type: { name: string; provisional: boolean } | null;
  next_theme: string | null;
  themes: {
    id: string;
    left: string;
    right: string;
    compared: number;
    minimum: number;
    done: number;
  }[];
};

/** Shared goal-gradient state: how far the user's answers are from a complete compass. */
export function useCompassProgress() {
  const auth = useAuthStore();
  const data = useState<CompassProgress | null>("compass-progress", () => null);
  let pending: Promise<void> | null = null;
  function refresh() {
    if (!auth.isLoggedIn) {
      data.value = null;
      return Promise.resolve();
    }
    pending ??= useApi()
      .get<CompassProgress>("/me/compass-progress")
      .then((result) => {
        data.value = result;
      })
      .catch(() => {
        /* Keep the last known progress; the bar is a hint, never a blocker. */
      })
      .finally(() => {
        pending = null;
      });
    return pending;
  }
  return { data, refresh };
}
