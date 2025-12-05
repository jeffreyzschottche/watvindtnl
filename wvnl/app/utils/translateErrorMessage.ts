const DEFAULT_FALLBACK = "Er ging iets mis. Probeer het later opnieuw.";

type TranslateErrorOptions = {
  fallback?: string;
  statusCode?: number;
};

type ErrorMapping = {
  test: RegExp;
  message: string;
};

const errorMappings: ErrorMapping[] = [
  {
    test: /invalid credentials/i,
    message:
      "Je e-mailadres is niet correct of je wachtwoord bevat te weinig tekens.",
  },
  {
    test: /incorrect email/i,
    message:
      "Je e-mailadres is niet correct of je wachtwoord bevat te weinig tekens.",
  },
  {
    test: /wrong password/i,
    message:
      "Je e-mailadres is niet correct of je wachtwoord bevat te weinig tekens.",
  },
  {
    test: /email.*(required|empty)/i,
    message: "Vul een e-mailadres in.",
  },
  {
    test: /password.*(required|empty)/i,
    message: "Vul je wachtwoord in.",
  },
  {
    test: /password.*(least|min|characters)/i,
    message: "Je wachtwoord moet minimaal 8 tekens bevatten.",
  },
  {
    test: /password confirmation/i,
    message: "Bevestig je wachtwoord en zorg dat het overeenkomt.",
  },
  {
    test: /email.*taken/i,
    message: "Dit e-mailadres is al bij ons bekend.",
  },
  {
    test: /user not found/i,
    message: "We konden dit account niet vinden.",
  },
  {
    test: /unauth/i,
    message: "Je sessie is verlopen. Log opnieuw in.",
  },
  {
    test: /(forbidden|unauthorized)/i,
    message: "Je hebt geen rechten om deze actie uit te voeren.",
  },
  {
    test: /too many (attempts|requests)/i,
    message: "Je hebt te vaak geprobeerd. Wacht even en probeer opnieuw.",
  },
  {
    test: /token.*invalid/i,
    message: "Deze link is ongeldig of verlopen. Vraag een nieuwe aan.",
  },
  {
    test: /not found/i,
    message: "We konden dit niet vinden.",
  },
  {
    test: /given data was invalid/i,
    message: "Controleer de invoer en probeer het opnieuw.",
  },
  {
    test: /failed to fetch/i,
    message:
      "We konden geen verbinding maken. Controleer je internetverbinding en probeer het opnieuw.",
  },
  {
    test: /network (error|request)/i,
    message:
      "We konden geen verbinding maken. Controleer je internetverbinding en probeer het opnieuw.",
  },
  {
    test: /server error/i,
    message: "Er ging iets mis aan onze kant. Probeer het later opnieuw.",
  },
];

export function translateErrorMessage(
  input: unknown,
  options: TranslateErrorOptions = {}
): string {
  const fallback = options.fallback ?? DEFAULT_FALLBACK;
  const normalized = normalizeInput(input);

  if (normalized) {
    for (const mapping of errorMappings) {
      if (mapping.test.test(normalized)) {
        return mapping.message;
      }
    }
  }

  const statusResult = translateByStatus(options.statusCode);
  if (statusResult) {
    return statusResult;
  }

  return normalized || fallback;
}

function normalizeInput(input: unknown): string | null {
  if (!input) {
    return null;
  }

  if (typeof input === "string") {
    return input.trim();
  }

  if (input instanceof Error) {
    return input.message.trim();
  }

  if (typeof input === "object" && "message" in (input as any)) {
    const value = (input as { message?: string }).message;
    return typeof value === "string" ? value.trim() : null;
  }

  const stringified = String(input);
  return stringified ? stringified.trim() : null;
}

function translateByStatus(statusCode?: number | null): string | null {
  if (!statusCode) return null;

  if (statusCode === 401 || statusCode === 419) {
    return "Je sessie is verlopen. Log opnieuw in.";
  }

  if (statusCode === 403) {
    return "Je hebt geen rechten om deze actie uit te voeren.";
  }

  if (statusCode === 404) {
    return "We konden dit niet vinden.";
  }

  if (statusCode === 422) {
    return "Controleer de invoer en probeer het opnieuw.";
  }

  if (statusCode === 429) {
    return "Je hebt te vaak geprobeerd. Wacht even en probeer opnieuw.";
  }

  if (statusCode >= 500) {
    return "Er ging iets mis aan onze kant. Probeer het later opnieuw.";
  }

  return null;
}
