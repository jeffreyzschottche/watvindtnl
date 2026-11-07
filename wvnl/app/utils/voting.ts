import type { IssueVoteCounts, IssueVoteResponse, IssueWithArguments } from "../types/issues";

export function applyVoteResult(issue: IssueWithArguments, result: IssueVoteResponse): IssueWithArguments {
  return { ...issue, votes: { ...result.votes }, user_vote: result.user_vote };
}

// Largest remainder rounding: displayed percentages always add up to 100.
export function votePercentages(counts: IssueVoteCounts): IssueVoteCounts {
  const keys = ["disagree", "agree", "neutral"] as const;
  const total = keys.reduce((sum, key) => sum + counts[key], 0);
  const result: IssueVoteCounts = { agree: 0, disagree: 0, neutral: 0 };
  if (!total) return result;
  const remainders = keys.map((key) => {
    const exact = counts[key] / total * 100;
    result[key] = Math.floor(exact);
    return { key, remainder: exact - result[key] };
  }).sort((a, b) => b.remainder - a.remainder);
  const missing = 100 - keys.reduce((sum, key) => sum + result[key], 0);
  for (let i = 0; i < missing; i++) result[remainders[i]!.key]++;
  return result;
}
