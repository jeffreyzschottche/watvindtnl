export type CompassProgress = {
  done: number;
  target: number;
  needed: number;
  used_votes: number;
  vote_count: number;
  remaining_votes: number;
  remaining_weighted_votes: number;
  report_ready: boolean;
  checkout_available: boolean;
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
  const complete = computed(() => data.value?.report_ready === true);
  const percent = computed(() =>
    data.value?.target ? Math.round((100 * data.value.done) / data.value.target) : 0,
  );
  const themesRemaining = computed(
    () => data.value?.themes.filter((theme) => theme.done < theme.minimum).length ?? 0,
  );
  const votesRemaining = computed(() =>
    Math.max(data.value?.remaining_votes ?? 0, data.value?.remaining_weighted_votes ?? 0),
  );
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
  return { data, complete, percent, themesRemaining, votesRemaining, refresh };
}
