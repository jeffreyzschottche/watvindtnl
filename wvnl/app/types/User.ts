export type Gender = "male" | "female" | "unspecified";

export interface NotificationPrefs {
  email?: boolean;
  push?: boolean;
  sms?: boolean;
}

export interface CookiePrefs {
  analytics?: boolean;
  marketing?: boolean;
  functional?: boolean;
}

export interface User {
  id: number;
  name: string;
  username?: string | null;
  email: string;

  // Wat-vindt-NL profielvelden
  voted_issue_ids?: number[]; // ID van de kwesties gestemd
  requests?: string[]; // Requests
  age_category?: string | null; // leeftijdscategorie
  province?: string | null; // provincie
  gender?: Gender;
  education_level?: string | null; // opleidingsniveau
  political_preference?: string | null; // politieke voorkeur
  notification_prefs?: NotificationPrefs | null;
  cookie_prefs?: CookiePrefs | null;
  premium?: boolean; // optioneel
  created_at?: string;
  updated_at?: string;
}
