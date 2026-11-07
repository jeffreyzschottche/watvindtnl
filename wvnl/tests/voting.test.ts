import { describe, expect, it } from "vitest";
import { applyVoteResult, votePercentages } from "../app/utils/voting";
import { adMode } from "../app/utils/ads";
import type { IssueVoteResponse, IssueWithArguments } from "../app/types/issues";

describe("participant result presentation", () => {
  it("keeps the same statement when a vote succeeds", () => {
    const issue = { id: 42, title: "Test", votes: null, user_vote: null } as IssueWithArguments;
    const result: IssueVoteResponse = { status: "ok", vote: "agree", user_vote: "agree", voted_issue_ids: [42], votes: { agree: 2, disagree: 1, neutral: 0 } };
    const updated = applyVoteResult(issue, result);
    expect(updated.id).toBe(42);
    expect(updated.user_vote).toBe("agree");
    expect(updated.votes).toEqual(result.votes);
    expect(issue.user_vote).toBeNull();
  });
  it("displays zero honestly for an empty result", () => {
    expect(votePercentages({ agree: 0, disagree: 0, neutral: 0 })).toEqual({ agree: 0, disagree: 0, neutral: 0 });
  });
  it("keeps historical neutral data and rounds to exactly 100", () => {
    const values = votePercentages({ agree: 1, disagree: 1, neutral: 1 });
    expect(values.agree + values.disagree + values.neutral).toBe(100);
    expect(values.neutral).toBeGreaterThan(0);
  });
  it("computes real binary percentages", () => {
    expect(votePercentages({ agree: 64, disagree: 36, neutral: 0 })).toEqual({ agree: 64, disagree: 36, neutral: 0 });
  });
});

describe("independent demo and real ads controls", () => {
  it.each([
    [true, false, false, "demo"], [false, false, false, "off"],
    [false, true, false, "off"], [true, true, false, "demo"],
    [false, false, true, "off"], [false, true, true, "live"],
  ] as const)("demo=%s enabled=%s ready=%s => %s", (demo, enabled, ready, expected) => {
    expect(adMode(demo, enabled, ready)).toBe(expected);
  });
});
