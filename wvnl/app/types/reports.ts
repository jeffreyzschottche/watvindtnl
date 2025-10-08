export type ReportReason =
  | "incorrect_information"
  | "offensive_information"
  | "issue_misworded"
  | "argument_misworded";

export type ReportCounts = Record<ReportReason, number>;

export interface ReportReasonOption {
  label: string;
  value: ReportReason;
}

export const REPORT_REASON_OPTIONS: ReportReasonOption[] = [
  {
    value: "incorrect_information",
    label: "Deze informatie is onjuist",
  },
  {
    value: "offensive_information",
    label: "Deze informatie is aanstootgevend",
  },
  {
    value: "issue_misworded",
    label: "Deze kwestie is verkeerd geformuleerd",
  },
  {
    value: "argument_misworded",
    label: "Dit argument is verkeerd geformuleerd",
  },
];

export function createEmptyReportCounts(): ReportCounts {
  return {
    incorrect_information: 0,
    offensive_information: 0,
    issue_misworded: 0,
    argument_misworded: 0,
  };
}
