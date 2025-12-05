<template>
  <div class="min-h-screen bg-slate-100 p-6 md:p-10 container">
    <div class="mx-auto max-w-6xl space-y-8">
      <header
        class="flex flex-col gap-3 rounded-xl bg-white p-6 shadow-sm md:flex-row md:items-center md:justify-between"
      >
        <div>
          <h1 class="text-2xl font-bold text-slate-900">
            Hier heb jij niks te zoeken...
          </h1>
          <p class="text-sm text-slate-500">Login of registreer</p>
        </div>
        <div
          class="rounded-lg bg-slate-100 px-4 py-3 text-sm text-slate-600"
        ></div>
      </header>

      <section
        v-if="!isLoggedIn"
        class="rounded-xl bg-white p-6 shadow-sm"
      ></section>

      <section v-else-if="!isAdmin" class="rounded-xl bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-red">Geen toegang</h2>
      </section>

      <section v-else class="space-y-6">
        <div class="flex flex-wrap items-center gap-3">
          <nav class="flex flex-wrap gap-2 rounded-lg bg-white p-2 shadow-sm">
            <button
              v-for="tab in tabs"
              :key="tab.key"
              type="button"
              :class="[
                'rounded-md px-4 py-2 text-sm font-semibold transition',
                activeTab === tab.key
                  ? 'bg-slate-900 text-white shadow'
                  : 'text-slate-600 hover:bg-slate-100',
              ]"
              @click="activeTab = tab.key"
            >
              {{ tab.label }}
            </button>
          </nav>
          <div class="flex items-center gap-3 text-sm text-slate-500">
            <button
              type="button"
              class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 font-medium text-slate-600 shadow-sm transition hover:bg-slate-100"
              @click="refreshAll"
            >
              <span
                class="inline-block h-2 w-2 rounded-full"
                :class="loading ? 'bg-amber-500' : 'bg-emerald-500'"
              ></span>
              {{ loading ? "Bezig met laden" : "Gegevens up-to-date" }}
            </button>
            <span
              v-if="error"
              class="flex items-center gap-2 rounded-lg bg-red-50 px-3 py-2 font-medium text-red-700"
            >
              <span>⚠️</span>
              <span>{{ error }}</span>
            </span>
          </div>
        </div>

        <div v-if="activeTab === 'issues'" class="space-y-6">
          <section class="rounded-xl bg-white p-6 shadow-sm">
            <header
              class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
            >
              <div>
                <h2 class="text-lg font-semibold text-slate-800">
                  Importeer issues
                </h2>
                <p class="text-sm text-slate-500">
                  Upload een JSON-bestand in hetzelfde formaat als de seeder om
                  meerdere issues en bijbehorende argumenten tegelijk toe te
                  voegen of te updaten.
                </p>
              </div>
              <div class="flex flex-wrap gap-2">
                <input
                  ref="issueUploadInput"
                  type="file"
                  accept="application/json"
                  class="hidden"
                  @change="handleIssueFileUpload"
                />
                <button
                  type="button"
                  class="inline-flex items-center rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100"
                  @click="triggerIssueUpload"
                >
                  JSON kiezen
                </button>
                <button
                  type="button"
                  class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-400"
                  :disabled="loading || !hasIssueImport"
                  @click="saveIssueImport"
                >
                  Opslaan
                </button>
              </div>
            </header>
            <div class="space-y-3 text-sm text-slate-600">
              <p>
                Ondersteunt een object met
                <code class="rounded bg-slate-100 px-1">issues</code> en
                optioneel
                <code class="rounded bg-slate-100 px-1">arguments</code>, of een
                array met issue-items.
              </p>
              <pre
                class="overflow-x-auto rounded-lg bg-slate-900 p-4 text-xs text-slate-100"
              ><code>{{ issueImportExample }}</code></pre>
              <div
                class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600"
              >
                <div class="flex flex-wrap items-center gap-2">
                  <input
                    ref="issueDirectoryInput"
                    type="file"
                    multiple
                    webkitdirectory
                    class="hidden"
                    @change="handleIssueDirectoryUpload"
                  />
                  <button
                    type="button"
                    class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100"
                    @click="triggerIssueDirectoryUpload"
                  >
                    Map kiezen
                  </button>
                  <button
                    type="button"
                    class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-400"
                    :disabled="!issueDirectoryState.motions.length"
                    @click="prepareIssueImportFromDirectory"
                  >
                    Selectie laden
                  </button>
                </div>
                <p class="mt-3 text-xs text-slate-500">
                  Kies de map
                  <code class="rounded bg-slate-100 px-1">output</code> met
                  submappen zoals
                  <code class="rounded bg-slate-100 px-1"
                    >motie1/motie1.json</code
                  >
                  en geef hieronder het gewenste bereik op.
                </p>
                <div
                  v-if="issueDirectoryState.motions.length"
                  class="mt-4 grid gap-3 md:grid-cols-2"
                >
                  <div class="grid gap-1">
                    <label class="text-xs font-semibold text-slate-600"
                      >Start motienummer</label
                    >
                    <input
                      v-model="issueDirectoryState.start"
                      type="number"
                      min="1"
                      inputmode="numeric"
                      class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                    />
                  </div>
                  <div class="grid gap-1">
                    <label class="text-xs font-semibold text-slate-600"
                      >Eind motienummer</label
                    >
                    <input
                      v-model="issueDirectoryState.end"
                      type="number"
                      min="1"
                      inputmode="numeric"
                      class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                    />
                  </div>
                </div>
                <p
                  v-if="issueDirectoryBounds"
                  class="mt-4 text-xs text-slate-500"
                >
                  Beschikbare moties: {{ issueDirectoryBounds.count }} (motie{{
                    issueDirectoryBounds.first
                  }}
                  - motie{{ issueDirectoryBounds.last }})
                </p>
                <p
                  v-if="issueDirectorySelectionPreview"
                  class="mt-1 text-xs text-slate-500"
                >
                  Geselecteerd bereik: motie{{
                    issueDirectorySelectionPreview.requestedStart
                  }}
                  - motie{{ issueDirectorySelectionPreview.requestedEnd }} ({{
                    issueDirectorySelectionPreview.count
                  }}
                  moties
                  <span
                    v-if="
                      issueDirectorySelectionPreview.availableFirst !== null &&
                      (issueDirectorySelectionPreview.availableFirst !==
                        issueDirectorySelectionPreview.requestedStart ||
                        issueDirectorySelectionPreview.availableLast !==
                          issueDirectorySelectionPreview.requestedEnd)
                    "
                    class="font-semibold text-slate-600"
                  >
                    • beschikbaar: motie{{
                      issueDirectorySelectionPreview.availableFirst
                    }}
                    - motie{{ issueDirectorySelectionPreview.availableLast }}
                  </span>
                  <span
                    v-if="issueDirectorySelectionPreview.missing > 0"
                    class="font-semibold text-amber-600"
                  >
                    • {{ issueDirectorySelectionPreview.missing }} ontbreken
                  </span>
                  )
                </p>
                <p
                  v-if="issueDirectoryState.error"
                  class="mt-3 text-sm font-semibold text-red-600"
                >
                  {{ issueDirectoryState.error }}
                </p>
              </div>
              <div
                v-if="issueImportState.fileName"
                class="rounded-lg border border-slate-200 bg-slate-50 p-4"
              >
                <p class="text-sm font-semibold text-slate-700">
                  Geselecteerd bestand
                </p>
                <p class="text-sm text-slate-600">
                  {{ issueImportState.fileName }}
                </p>
                <p class="mt-2 text-xs text-slate-500">
                  Issues: {{ issueImportState.issues.length }} • Argumenten:
                  {{ issueImportState.arguments.length }}
                </p>
                <p
                  v-if="issueImportState.error"
                  class="mt-2 text-sm font-semibold text-red-600"
                >
                  {{ issueImportState.error }}
                </p>
                <button
                  type="button"
                  class="mt-3 inline-flex items-center rounded-md border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100"
                  @click="resetIssueImportState"
                >
                  Verwijderen
                </button>
              </div>
            </div>
          </section>

          <section class="rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-4 flex items-center justify-between">
              <div>
                <h2 class="text-lg font-semibold text-slate-800">
                  Nieuwe kwestie toevoegen
                </h2>
                <p class="text-sm text-slate-500">
                  Vul de basisgegevens in en voeg optioneel alvast argumenten
                  toe.
                </p>
              </div>
            </header>
            <form class="grid gap-4" @submit.prevent="submitIssue">
              <div class="grid gap-2">
                <label class="text-sm font-medium text-slate-700">Titel</label>
                <input
                  v-model="newIssueForm.title"
                  type="text"
                  required
                  class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                />
              </div>
              <div class="grid gap-2 md:grid-cols-2 md:gap-4">
                <div class="grid gap-2">
                  <label class="text-sm font-medium text-slate-700"
                    >Slug (optioneel)</label
                  >
                  <input
                    v-model="newIssueForm.slug"
                    type="text"
                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                  />
                </div>
                <div class="grid gap-2">
                  <label class="text-sm font-medium text-slate-700"
                    >Bron URL (optioneel)</label
                  >
                  <input
                    v-model="newIssueForm.url"
                    type="url"
                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                  />
                </div>
              </div>
              <div class="grid gap-2">
                <label class="text-sm font-medium text-slate-700"
                  >Korte beschrijving</label
                >
                <textarea
                  v-model="newIssueForm.description"
                  rows="3"
                  class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                ></textarea>
              </div>
              <div class="grid gap-2">
                <label class="text-sm font-medium text-slate-700"
                  >Uitgebreide toelichting</label
                >
                <textarea
                  v-model="newIssueForm.more_info"
                  rows="4"
                  class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                ></textarea>
              </div>

              <div class="grid gap-4 md:grid-cols-3">
                <div class="grid gap-2">
                  <label class="text-sm font-semibold text-slate-700"
                    >Voor (agree)</label
                  >
                  <select
                    v-model="newIssueForm.stances.agree"
                    multiple
                    class="h-32 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                  >
                    <option
                      v-for="party in parties"
                      :key="party.id"
                      :value="party.id"
                    >
                      {{ party.abbreviation || party.name }}
                    </option>
                  </select>
                </div>
                <div class="grid gap-2">
                  <label class="text-sm font-semibold text-slate-700"
                    >Neutraal</label
                  >
                  <select
                    v-model="newIssueForm.stances.neutral"
                    multiple
                    class="h-32 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                  >
                    <option
                      v-for="party in parties"
                      :key="party.id"
                      :value="party.id"
                    >
                      {{ party.abbreviation || party.name }}
                    </option>
                  </select>
                </div>
                <div class="grid gap-2">
                  <label class="text-sm font-semibold text-slate-700"
                    >Tegen (disagree)</label
                  >
                  <select
                    v-model="newIssueForm.stances.disagree"
                    multiple
                    class="h-32 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                  >
                    <option
                      v-for="party in parties"
                      :key="party.id"
                      :value="party.id"
                    >
                      {{ party.abbreviation || party.name }}
                    </option>
                  </select>
                </div>
              </div>

              <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-lg border border-slate-200 p-4">
                  <header class="mb-3 flex items-center justify-between">
                    <div>
                      <h3 class="text-sm font-semibold text-slate-800">
                        Voor argumenten
                      </h3>
                      <p class="text-xs text-slate-500">
                        Voeg argumenten toe zoals in de seeder.
                      </p>
                    </div>
                    <button
                      type="button"
                      class="text-xs font-semibold text-slate-600 hover:text-slate-900"
                      @click="addArgumentField('pro')"
                    >
                      + Argument
                    </button>
                  </header>
                  <div class="space-y-4">
                    <div
                      v-for="(argument, index) in newIssueForm.proArguments"
                      :key="`pro-${index}`"
                      class="rounded-lg bg-slate-50 p-3"
                    >
                      <label class="text-xs font-semibold text-slate-600"
                        >Inhoud</label
                      >
                      <textarea
                        v-model="argument.body"
                        rows="3"
                        class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                      ></textarea>
                      <label
                        class="mt-3 block text-xs font-semibold text-slate-600"
                        >Bronnen (één per regel)</label
                      >
                      <textarea
                        v-model="argument.sources"
                        rows="2"
                        class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                      ></textarea>
                      <button
                        v-if="newIssueForm.proArguments.length > 1"
                        type="button"
                        class="mt-3 inline-flex items-center rounded-md border border-red-200 px-3 py-1 text-xs font-semibold text-red-600 hover:bg-red-50"
                        @click="removeArgumentField('pro', index)"
                      >
                        Verwijderen
                      </button>
                    </div>
                  </div>
                </div>
                <div class="rounded-lg border border-slate-200 p-4">
                  <header class="mb-3 flex items-center justify-between">
                    <div>
                      <h3 class="text-sm font-semibold text-slate-800">
                        Tegen argumenten
                      </h3>
                      <p class="text-xs text-slate-500">
                        Voeg tegenargumenten toe (optioneel).
                      </p>
                    </div>
                    <button
                      type="button"
                      class="text-xs font-semibold text-slate-600 hover:text-slate-900"
                      @click="addArgumentField('con')"
                    >
                      + Argument
                    </button>
                  </header>
                  <div class="space-y-4">
                    <div
                      v-for="(argument, index) in newIssueForm.conArguments"
                      :key="`con-${index}`"
                      class="rounded-lg bg-slate-50 p-3"
                    >
                      <label class="text-xs font-semibold text-slate-600"
                        >Inhoud</label
                      >
                      <textarea
                        v-model="argument.body"
                        rows="3"
                        class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                      ></textarea>
                      <label
                        class="mt-3 block text-xs font-semibold text-slate-600"
                        >Bronnen (één per regel)</label
                      >
                      <textarea
                        v-model="argument.sources"
                        rows="2"
                        class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                      ></textarea>
                      <button
                        v-if="newIssueForm.conArguments.length > 1"
                        type="button"
                        class="mt-3 inline-flex items-center rounded-md border border-red-200 px-3 py-1 text-xs font-semibold text-red-600 hover:bg-red-50"
                        @click="removeArgumentField('con', index)"
                      >
                        Verwijderen
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="flex justify-end">
                <button
                  type="submit"
                  class="inline-flex items-center rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-400"
                  :disabled="loading"
                >
                  {{ loading ? "Bezig met opslaan..." : "Kwestie opslaan" }}
                </button>
              </div>
            </form>
          </section>

          <section class="rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-4 flex items-center justify-between">
              <div>
                <h2 class="text-lg font-semibold text-slate-800">
                  Bestaande kwesties
                </h2>
                <p class="text-sm text-slate-500">
                  Bekijk alle kwesties inclusief argumenten en meldingen.
                </p>
              </div>
              <span
                class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600"
              >
                {{ issues.length }} kwesties
              </span>
            </header>
            <div
              v-if="!issues.length"
              class="rounded-lg border border-dashed border-slate-300 p-8 text-center text-slate-500"
            >
              Nog geen kwesties gevonden. Voeg de eerste kwestie toe via het
              formulier hierboven.
            </div>
            <ul v-else class="space-y-4">
              <li
                v-for="issue in issues"
                :key="issue.id"
                class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"
              >
                <div v-if="editingIssueId === issue.id" class="space-y-4">
                  <div class="grid gap-3 md:grid-cols-2 md:gap-4">
                    <div class="grid gap-2">
                      <label class="text-xs font-semibold text-slate-600"
                        >Titel</label
                      >
                      <input
                        v-model="issueDraft.title"
                        type="text"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                      />
                    </div>
                    <div class="grid gap-2">
                      <label class="text-xs font-semibold text-slate-600"
                        >Slug</label
                      >
                      <input
                        v-model="issueDraft.slug"
                        type="text"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                      />
                    </div>
                  </div>
                  <div class="grid gap-3 md:grid-cols-2 md:gap-4">
                    <div class="grid gap-2">
                      <label class="text-xs font-semibold text-slate-600"
                        >Bron URL</label
                      >
                      <input
                        v-model="issueDraft.url"
                        type="text"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                      />
                    </div>
                    <div class="grid gap-2">
                      <label class="text-xs font-semibold text-slate-600"
                        >Meer info</label
                      >
                      <textarea
                        v-model="issueDraft.more_info"
                        rows="3"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                      ></textarea>
                    </div>
                  </div>
                  <div class="grid gap-2">
                    <label class="text-xs font-semibold text-slate-600"
                      >Beschrijving</label
                    >
                    <textarea
                      v-model="issueDraft.description"
                      rows="3"
                      class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                    ></textarea>
                  </div>
                  <div class="grid gap-4 md:grid-cols-3">
                    <div class="grid gap-2">
                      <label class="text-xs font-semibold text-slate-600"
                        >Voor (agree)</label
                      >
                      <select
                        v-model="issueDraft.stances.agree"
                        multiple
                        class="h-28 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                      >
                        <option
                          v-for="party in parties"
                          :key="party.id"
                          :value="party.id"
                        >
                          {{ party.abbreviation || party.name }}
                        </option>
                      </select>
                    </div>
                    <div class="grid gap-2">
                      <label class="text-xs font-semibold text-slate-600"
                        >Neutraal</label
                      >
                      <select
                        v-model="issueDraft.stances.neutral"
                        multiple
                        class="h-28 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                      >
                        <option
                          v-for="party in parties"
                          :key="`neutral-${party.id}`"
                          :value="party.id"
                        >
                          {{ party.abbreviation || party.name }}
                        </option>
                      </select>
                    </div>
                    <div class="grid gap-2">
                      <label class="text-xs font-semibold text-slate-600"
                        >Tegen (disagree)</label
                      >
                      <select
                        v-model="issueDraft.stances.disagree"
                        multiple
                        class="h-28 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                      >
                        <option
                          v-for="party in parties"
                          :key="`disagree-${party.id}`"
                          :value="party.id"
                        >
                          {{ party.abbreviation || party.name }}
                        </option>
                      </select>
                    </div>
                  </div>
                  <div class="flex flex-wrap gap-2">
                    <button
                      type="button"
                      class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-400"
                      :disabled="loading"
                      @click="saveIssueEdit(issue.id)"
                    >
                      Opslaan
                    </button>
                    <button
                      type="button"
                      class="inline-flex items-center rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100"
                      @click="cancelIssueEdit"
                    >
                      Annuleren
                    </button>
                  </div>
                </div>
                <div v-else class="space-y-4">
                  <div
                    class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between"
                  >
                    <div>
                      <h3 class="text-lg font-semibold text-slate-900">
                        {{ issue.title }}
                      </h3>
                      <p class="text-xs uppercase tracking-wide text-slate-400">
                        Slug: {{ issue.slug }}
                      </p>
                      <p v-if="issue.url" class="mt-1 text-sm text-slate-500">
                        Bron: {{ issue.url }}
                      </p>
                      <p
                        v-if="issue.description"
                        class="mt-2 text-sm text-slate-600"
                      >
                        {{ issue.description }}
                      </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                      <button
                        type="button"
                        class="self-start rounded-md border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100"
                        @click="startIssueEdit(issue)"
                      >
                        Bewerken
                      </button>
                      <button
                        type="button"
                        class="self-start rounded-md border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50"
                        @click="confirmDeleteIssue(issue.id, issue.title)"
                      >
                        Verwijderen
                      </button>
                    </div>
                  </div>
                  <div class="grid gap-3 text-sm text-slate-600 md:grid-cols-3">
                    <div>
                      <p class="font-semibold text-slate-700">Argumenten</p>
                      <p>Voor: {{ issue.arguments.pro.length }}</p>
                      <p>Tegen: {{ issue.arguments.con.length }}</p>
                    </div>
                    <div>
                      <p class="font-semibold text-slate-700">Partijen</p>
                      <p class="text-xs text-slate-500">
                        Voor: {{ resolvePartyList(issue.party_stances.agree) }}
                      </p>
                      <p class="text-xs text-slate-500">
                        Neutraal:
                        {{ resolvePartyList(issue.party_stances.neutral) }}
                      </p>
                      <p class="text-xs text-slate-500">
                        Tegen:
                        {{ resolvePartyList(issue.party_stances.disagree) }}
                      </p>
                    </div>
                    <div>
                      <p class="font-semibold text-slate-700">Meldingen</p>
                      <p>Onjuist: {{ issue.reports.incorrect_information }}</p>
                      <p>
                        Aanstootgevend:
                        {{ issue.reports.offensive_information }}
                      </p>
                      <p>
                        Kwestie verkeerd: {{ issue.reports.issue_misworded }}
                      </p>
                      <p>
                        Argument verkeerd:
                        {{ issue.reports.argument_misworded }}
                      </p>
                    </div>
                  </div>
                </div>
              </li>
            </ul>
          </section>
        </div>

        <div v-if="activeTab === 'arguments'" class="space-y-6">
          <section class="rounded-xl bg-white p-6 shadow-sm">
            <header
              class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
            >
              <div>
                <h2 class="text-lg font-semibold text-slate-800">
                  Importeer argumenten
                </h2>
                <p class="text-sm text-slate-500">
                  Upload een JSON-bestand met argumenten gekoppeld via
                  <code class="rounded bg-slate-100 px-1">issue_id</code> of
                  <code class="rounded bg-slate-100 px-1">issue_slug</code>.
                </p>
              </div>
              <div class="flex flex-wrap gap-2">
                <input
                  ref="argumentUploadInput"
                  type="file"
                  accept="application/json"
                  class="hidden"
                  @change="handleArgumentFileUpload"
                />
                <button
                  type="button"
                  class="inline-flex items-center rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100"
                  @click="triggerArgumentUpload"
                >
                  JSON kiezen
                </button>
                <button
                  type="button"
                  class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-400"
                  :disabled="loading || !hasArgumentImport"
                  @click="saveArgumentImport"
                >
                  Opslaan
                </button>
              </div>
            </header>
            <div class="space-y-3 text-sm text-slate-600">
              <p>
                Ondersteunt een object met
                <code class="rounded bg-slate-100 px-1">arguments</code> of een
                array met argumentitems.
              </p>
              <pre
                class="overflow-x-auto rounded-lg bg-slate-900 p-4 text-xs text-slate-100"
              ><code>{{ argumentImportExample }}</code></pre>
              <div
                v-if="argumentImportState.fileName"
                class="rounded-lg border border-slate-200 bg-slate-50 p-4"
              >
                <p class="text-sm font-semibold text-slate-700">
                  Geselecteerd bestand
                </p>
                <p class="text-sm text-slate-600">
                  {{ argumentImportState.fileName }}
                </p>
                <p class="mt-2 text-xs text-slate-500">
                  Argumenten: {{ argumentImportState.arguments.length }}
                </p>
                <p
                  v-if="argumentImportState.error"
                  class="mt-2 text-sm font-semibold text-red-600"
                >
                  {{ argumentImportState.error }}
                </p>
                <button
                  type="button"
                  class="mt-3 inline-flex items-center rounded-md border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100"
                  @click="resetArgumentImportState"
                >
                  Verwijderen
                </button>
              </div>
            </div>
          </section>

          <section class="rounded-xl bg-white p-6 shadow-sm">
            <header
              class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
            >
              <div>
                <h2 class="text-lg font-semibold text-slate-800">
                  Argumenten beheren
                </h2>
                <p class="text-sm text-slate-500">
                  Voeg argumenten toe aan een bestaande kwestie of verwijder ze.
                </p>
              </div>
              <select
                v-model.number="selectedIssueId"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none md:w-72"
              >
                <option disabled value="">Kies een kwestie</option>
                <option
                  v-for="issue in issues"
                  :key="issue.id"
                  :value="issue.id"
                >
                  {{ issue.title }}
                </option>
              </select>
            </header>

            <div
              v-if="!issues.length"
              class="rounded-lg border border-dashed border-slate-300 p-8 text-center text-slate-500"
            >
              Voeg eerst een kwestie toe voordat je argumenten kunt beheren.
            </div>

            <div
              v-else-if="!selectedIssue"
              class="rounded-lg border border-dashed border-slate-300 p-8 text-center text-slate-500"
            >
              Selecteer een kwestie om argumenten te bekijken of toe te voegen.
            </div>

            <div v-else class="space-y-6">
              <div class="rounded-lg border border-slate-200 p-4">
                <h3 class="text-sm font-semibold text-slate-800">
                  Nieuw argument toevoegen voor "{{ selectedIssue.title }}"
                </h3>
                <form
                  class="mt-4 grid gap-4 md:grid-cols-2"
                  @submit.prevent="submitArgument"
                >
                  <div class="grid gap-2">
                    <label class="text-xs font-semibold text-slate-600"
                      >Zijde</label
                    >
                    <select
                      v-model="argumentForm.side"
                      class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                    >
                      <option value="pro">Voor</option>
                      <option value="con">Tegen</option>
                    </select>
                  </div>
                  <div class="grid gap-2 md:col-span-2">
                    <label class="text-xs font-semibold text-slate-600"
                      >Argumenttekst</label
                    >
                    <textarea
                      v-model="argumentForm.body"
                      rows="4"
                      required
                      class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                    ></textarea>
                  </div>
                  <div class="grid gap-2 md:col-span-2">
                    <label class="text-xs font-semibold text-slate-600"
                      >Bronnen (één per regel)</label
                    >
                    <textarea
                      v-model="argumentForm.sources"
                      rows="3"
                      class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                    ></textarea>
                  </div>
                  <div class="md:col-span-2 flex justify-end">
                    <button
                      type="submit"
                      class="inline-flex items-center rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-400"
                      :disabled="loading"
                    >
                      {{ loading ? "Opslaan..." : "Argument toevoegen" }}
                    </button>
                  </div>
                </form>
              </div>

              <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-lg border border-slate-200 p-4">
                  <h3 class="mb-3 text-sm font-semibold text-slate-800">
                    Voor argumenten ({{ selectedIssue.arguments.pro.length }})
                  </h3>
                  <div
                    v-if="!selectedIssue.arguments.pro.length"
                    class="rounded-lg border border-dashed border-slate-300 p-6 text-center text-xs text-slate-500"
                  >
                    Nog geen voor argumenten.
                  </div>
                  <ul v-else class="space-y-3">
                    <li
                      v-for="argument in selectedIssue.arguments.pro"
                      :key="argument.id"
                      class="rounded-lg bg-slate-50 p-3 text-sm text-slate-700"
                    >
                      <div
                        v-if="editingArgumentId === argument.id"
                        class="space-y-3"
                      >
                        <div class="grid gap-2">
                          <label class="text-xs font-semibold text-slate-600"
                            >Zijde</label
                          >
                          <select
                            v-model="argumentEditDraft.side"
                            class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                          >
                            <option value="pro">Voor</option>
                            <option value="con">Tegen</option>
                          </select>
                        </div>
                        <div class="grid gap-2">
                          <label class="text-xs font-semibold text-slate-600"
                            >Argumenttekst</label
                          >
                          <textarea
                            v-model="argumentEditDraft.body"
                            rows="4"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                          ></textarea>
                        </div>
                        <div class="grid gap-2">
                          <label class="text-xs font-semibold text-slate-600"
                            >Bronnen (één per regel)</label
                          >
                          <textarea
                            v-model="argumentEditDraft.sources"
                            rows="3"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                          ></textarea>
                        </div>
                        <div class="flex flex-wrap gap-2">
                          <button
                            type="button"
                            class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-400"
                            :disabled="loading"
                            @click="saveArgumentEdit(argument.id)"
                          >
                            Opslaan
                          </button>
                          <button
                            type="button"
                            class="inline-flex items-center rounded-lg border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100"
                            @click="cancelArgumentEdit"
                          >
                            Annuleren
                          </button>
                        </div>
                      </div>
                      <div v-else>
                        <p class="whitespace-pre-line">{{ argument.body }}</p>
                        <ul
                          v-if="argument.sources.length"
                          class="mt-2 list-disc pl-5 text-xs text-slate-500"
                        >
                          <li
                            v-for="(source, index) in argument.sources"
                            :key="index"
                          >
                            {{ source }}
                          </li>
                        </ul>
                        <div class="mt-3 flex flex-wrap gap-2">
                          <button
                            type="button"
                            class="inline-flex items-center rounded-md border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100"
                            @click="startEditArgument(argument)"
                          >
                            Bewerken
                          </button>
                          <button
                            type="button"
                            class="inline-flex items-center rounded-md border border-red-200 px-3 py-1 text-xs font-semibold text-red-600 hover:bg-red-50"
                            @click="removeArgument(argument.id)"
                          >
                            Verwijderen
                          </button>
                        </div>
                      </div>
                    </li>
                  </ul>
                </div>
                <div class="rounded-lg border border-slate-200 p-4">
                  <h3 class="mb-3 text-sm font-semibold text-slate-800">
                    Tegen argumenten ({{ selectedIssue.arguments.con.length }})
                  </h3>
                  <div
                    v-if="!selectedIssue.arguments.con.length"
                    class="rounded-lg border border-dashed border-slate-300 p-6 text-center text-xs text-slate-500"
                  >
                    Nog geen tegenargumenten.
                  </div>
                  <ul v-else class="space-y-3">
                    <li
                      v-for="argument in selectedIssue.arguments.con"
                      :key="argument.id"
                      class="rounded-lg bg-slate-50 p-3 text-sm text-slate-700"
                    >
                      <div
                        v-if="editingArgumentId === argument.id"
                        class="space-y-3"
                      >
                        <div class="grid gap-2">
                          <label class="text-xs font-semibold text-slate-600"
                            >Zijde</label
                          >
                          <select
                            v-model="argumentEditDraft.side"
                            class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                          >
                            <option value="pro">Voor</option>
                            <option value="con">Tegen</option>
                          </select>
                        </div>
                        <div class="grid gap-2">
                          <label class="text-xs font-semibold text-slate-600"
                            >Argumenttekst</label
                          >
                          <textarea
                            v-model="argumentEditDraft.body"
                            rows="4"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                          ></textarea>
                        </div>
                        <div class="grid gap-2">
                          <label class="text-xs font-semibold text-slate-600"
                            >Bronnen (één per regel)</label
                          >
                          <textarea
                            v-model="argumentEditDraft.sources"
                            rows="3"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                          ></textarea>
                        </div>
                        <div class="flex flex-wrap gap-2">
                          <button
                            type="button"
                            class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-400"
                            :disabled="loading"
                            @click="saveArgumentEdit(argument.id)"
                          >
                            Opslaan
                          </button>
                          <button
                            type="button"
                            class="inline-flex items-center rounded-lg border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100"
                            @click="cancelArgumentEdit"
                          >
                            Annuleren
                          </button>
                        </div>
                      </div>
                      <div v-else>
                        <p class="whitespace-pre-line">{{ argument.body }}</p>
                        <ul
                          v-if="argument.sources.length"
                          class="mt-2 list-disc pl-5 text-xs text-slate-500"
                        >
                          <li
                            v-for="(source, index) in argument.sources"
                            :key="index"
                          >
                            {{ source }}
                          </li>
                        </ul>
                        <div class="mt-3 flex flex-wrap gap-2">
                          <button
                            type="button"
                            class="inline-flex items-center rounded-md border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100"
                            @click="startEditArgument(argument)"
                          >
                            Bewerken
                          </button>
                          <button
                            type="button"
                            class="inline-flex items-center rounded-md border border-red-200 px-3 py-1 text-xs font-semibold text-red-600 hover:bg-red-50"
                            @click="removeArgument(argument.id)"
                          >
                            Verwijderen
                          </button>
                        </div>
                      </div>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </section>
        </div>

        <div v-if="activeTab === 'reports'" class="space-y-6">
          <section class="rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-4 flex items-center justify-between">
              <div>
                <h2 class="text-lg font-semibold text-slate-800">
                  Meldingenoverzicht
                </h2>
                <p class="text-sm text-slate-500">
                  Bekijk alle meldingen per kwestie op één plek.
                </p>
              </div>
              <button
                type="button"
                class="rounded-md border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100"
                @click="loadReports()"
              >
                Vernieuwen
              </button>
            </header>
            <div
              v-if="!reports.length"
              class="rounded-lg border border-dashed border-slate-300 p-8 text-center text-slate-500"
            >
              Geen meldingen beschikbaar.
            </div>
            <div v-else class="overflow-x-auto">
              <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead
                  class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"
                >
                  <tr>
                    <th class="px-4 py-3 text-left font-semibold">Kwestie</th>
                    <th class="px-4 py-3 text-left font-semibold">
                      Onjuiste info
                    </th>
                    <th class="px-4 py-3 text-left font-semibold">
                      Aanstootgevend
                    </th>
                    <th class="px-4 py-3 text-left font-semibold">
                      Kwestie verkeerd
                    </th>
                    <th class="px-4 py-3 text-left font-semibold">
                      Argument verkeerd
                    </th>
                    <th class="px-4 py-3 text-left font-semibold">
                      Laatste update
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                  <tr
                    v-for="issue in reports"
                    :key="`report-${issue.id}`"
                    class="bg-white"
                  >
                    <td class="px-4 py-3 font-medium text-slate-800">
                      {{ issue.title }}
                    </td>
                    <td class="px-4 py-3 text-slate-600">
                      {{ issue.reports.incorrect_information }}
                    </td>
                    <td class="px-4 py-3 text-slate-600">
                      {{ issue.reports.offensive_information }}
                    </td>
                    <td class="px-4 py-3 text-slate-600">
                      {{ issue.reports.issue_misworded }}
                    </td>
                    <td class="px-4 py-3 text-slate-600">
                      {{ issue.reports.argument_misworded }}
                    </td>
                    <td class="px-4 py-3 text-slate-500">
                      {{ formatDate(issue.updated_at) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>
        </div>

        <div v-if="activeTab === 'parties'" class="space-y-6">
          <section class="rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-4">
              <h2 class="text-lg font-semibold text-slate-800">
                Nieuwe politieke partij
              </h2>
              <p class="text-sm text-slate-500">
                Voeg een partij toe met alle velden uit de seeder.
              </p>
            </header>
            <form
              class="grid gap-4 md:grid-cols-2"
              @submit.prevent="submitParty"
            >
              <div class="grid gap-2">
                <label class="text-sm font-medium text-slate-700">Naam</label>
                <input
                  v-model="newPartyForm.name"
                  required
                  type="text"
                  class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                />
              </div>
              <div class="grid gap-2">
                <label class="text-sm font-medium text-slate-700"
                  >Afkorting</label
                >
                <input
                  v-model="newPartyForm.abbreviation"
                  required
                  type="text"
                  class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                />
              </div>
              <div class="grid gap-2">
                <label class="text-sm font-medium text-slate-700"
                  >Slug (optioneel)</label
                >
                <input
                  v-model="newPartyForm.slug"
                  type="text"
                  class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                />
              </div>
              <div class="grid gap-2 md:col-span-2">
                <label class="text-sm font-medium text-slate-700">Logo</label>
                <input
                  ref="newPartyLogoInput"
                  type="file"
                  accept="image/*"
                  class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                  @change="handleNewPartyLogoChange"
                />
                <div
                  v-if="newPartyForm.logoPreview"
                  class="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2"
                >
                  <img
                    :src="newPartyForm.logoPreview"
                    alt="Voorbeeld van partijlogo"
                    class="h-10 w-10 rounded-full bg-white p-1 object-contain"
                  />
                  <div class="flex flex-1 flex-col text-xs text-slate-600">
                    <span class="font-semibold">
                      {{ newPartyForm.logoFile?.name || "Voorbeeld" }}
                    </span>
                    <span class="truncate">{{ newPartyForm.logoPreview }}</span>
                  </div>
                  <button
                    type="button"
                    class="text-xs font-semibold text-red-600 hover:underline"
                    @click="clearNewPartyLogo"
                  >
                    Verwijderen
                  </button>
                </div>
                <div class="grid gap-1">
                  <label class="text-xs font-semibold text-slate-600">
                    Of gebruik een bestaande URL
                  </label>
                  <input
                    v-model="newPartyForm.logo_url"
                    type="text"
                    placeholder="/storage/logos/voorbeeld.png"
                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                  />
                </div>
              </div>
              <div class="md:col-span-2 grid gap-2">
                <label class="text-sm font-medium text-slate-700"
                  >Website URL</label
                >
                <input
                  v-model="newPartyForm.website_url"
                  type="url"
                  class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                />
              </div>
              <div class="md:col-span-2 flex justify-end">
                <button
                  type="submit"
                  class="inline-flex items-center rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-400"
                  :disabled="loading"
                >
                  {{ loading ? "Toevoegen..." : "Partij opslaan" }}
                </button>
              </div>
            </form>
          </section>

          <section class="rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-4 flex items-center justify-between">
              <div>
                <h2 class="text-lg font-semibold text-slate-800">
                  Bestaande partijen
                </h2>
                <p class="text-sm text-slate-500">
                  Pas bestaande partijen aan en beheer logo's en links.
                </p>
              </div>
              <span
                class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600"
              >
                {{ parties.length }} partijen
              </span>
            </header>
            <div
              v-if="!parties.length"
              class="rounded-lg border border-dashed border-slate-300 p-8 text-center text-slate-500"
            >
              Nog geen partijen beschikbaar.
            </div>
            <ul v-else class="space-y-4">
              <li
                v-for="party in parties"
                :key="party.id"
                class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"
              >
                <div v-if="editingPartyId === party.id" class="space-y-3">
                  <div class="grid gap-2 md:grid-cols-2 md:gap-4">
                    <div class="grid gap-2">
                      <label class="text-xs font-semibold text-slate-600"
                        >Naam</label
                      >
                      <input
                        v-model="partyDraft.name"
                        type="text"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                      />
                    </div>
                    <div class="grid gap-2">
                      <label class="text-xs font-semibold text-slate-600"
                        >Afkorting</label
                      >
                      <input
                        v-model="partyDraft.abbreviation"
                        type="text"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                      />
                    </div>
                  </div>
                  <div class="grid gap-2 md:grid-cols-3 md:gap-4">
                    <div class="grid gap-2">
                      <label class="text-xs font-semibold text-slate-600"
                        >Slug</label
                      >
                      <input
                        v-model="partyDraft.slug"
                        type="text"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                      />
                    </div>
                    <div class="grid gap-2 md:col-span-2">
                      <label class="text-xs font-semibold text-slate-600"
                        >Logo</label
                      >
                      <input
                        :ref="setEditPartyLogoInput"
                        type="file"
                        accept="image/*"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                        @change="handleEditPartyLogoChange"
                      />
                      <div
                        v-if="partyDraft.logoPreview"
                        class="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2"
                      >
                        <img
                          :src="partyDraft.logoPreview"
                          :alt="`Logo ${
                            partyDraft.abbreviation ||
                            partyDraft.name ||
                            'partij'
                          }`"
                          class="h-10 w-10 rounded-full bg-white p-1 object-contain"
                        />
                        <div
                          class="flex flex-1 flex-col text-xs text-slate-600"
                        >
                          <span class="font-semibold">
                            {{ partyDraft.logoFile?.name || "Voorbeeld" }}
                          </span>
                          <span class="truncate">{{
                            partyDraft.logoPreview
                          }}</span>
                        </div>
                        <button
                          type="button"
                          class="text-xs font-semibold text-red-600 hover:underline"
                          @click="clearEditPartyLogo"
                        >
                          Verwijderen
                        </button>
                      </div>
                      <div class="grid gap-1">
                        <label class="text-[11px] font-semibold text-slate-600">
                          Of gebruik een bestaande URL
                        </label>
                        <input
                          v-model="partyDraft.logo_url"
                          type="text"
                          class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                        />
                      </div>
                    </div>
                  </div>
                  <div class="grid gap-2">
                    <label class="text-xs font-semibold text-slate-600"
                      >Website URL</label
                    >
                    <input
                      v-model="partyDraft.website_url"
                      type="url"
                      class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                    />
                  </div>
                  <div class="flex flex-wrap gap-2">
                    <button
                      type="button"
                      class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
                      @click="saveParty(party.id)"
                    >
                      Opslaan
                    </button>
                    <button
                      type="button"
                      class="inline-flex items-center rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100"
                      @click="cancelEdit()"
                    >
                      Annuleren
                    </button>
                  </div>
                </div>
                <div
                  v-else
                  class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between"
                >
                  <div class="flex items-start gap-4">
                    <div
                      v-if="party.logo_url"
                      class="mt-1 flex h-14 w-14 items-center justify-center overflow-hidden rounded-full border border-slate-200 bg-white p-2"
                    >
                      <img
                        :src="party.logo_url"
                        :alt="`Logo ${party.abbreviation || party.name}`"
                        class="h-full w-full object-contain"
                      />
                    </div>
                    <div>
                      <h3 class="text-lg font-semibold text-slate-900">
                        {{ party.name }}
                      </h3>
                      <p class="text-xs uppercase tracking-wide text-slate-400">
                        {{ party.abbreviation }} • {{ party.slug }}
                      </p>
                      <p
                        v-if="party.logo_url"
                        class="mt-1 text-sm text-slate-500"
                      >
                        Logo:
                        <span class="break-all text-slate-600">{{
                          party.logo_url
                        }}</span>
                      </p>
                      <p
                        v-if="party.website_url"
                        class="mt-1 text-sm text-slate-500"
                      >
                        Website:
                        <span class="break-all text-slate-600">{{
                          party.website_url
                        }}</span>
                      </p>
                    </div>
                  </div>
                  <button
                    type="button"
                    class="self-start rounded-md border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100"
                    @click="startEdit(party)"
                  >
                    Bewerken
                  </button>
                </div>
              </li>
            </ul>
          </section>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  computed,
  onBeforeUnmount,
  onMounted,
  reactive,
  ref,
  watch,
} from "vue";
import { useAdmin } from "~/composables/useAdmin";
import type {
  AdminArgumentImportPayload,
  AdminArgumentPayload,
  AdminArgumentUpsertPayload,
  AdminIssue,
  AdminIssueImportItem,
  AdminIssueImportPayload,
  AdminIssuePayload,
  AdminPoliticalParty,
  AdminPoliticalPartyPayload,
} from "~/types/admin";

import { useRouter } from "vue-router";
import { translateErrorMessage } from "~/utils/translateErrorMessage";

const {
  auth,
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
  updateIssue,
  importIssues,
  deleteIssue,
  addArgument,
  updateArgument,
  importArguments,
  removeArgument: removeArgumentApi,
  createParty,
  updateParty,
  ADMIN_EMAIL,
  ADMIN_USERNAME,
} = useAdmin();

const tabs = [
  { key: "issues", label: "Issues" },
  { key: "arguments", label: "Argumenten" },
  { key: "reports", label: "Reports" },
  { key: "parties", label: "Partijen" },
] as const;

type TabKey = (typeof tabs)[number]["key"];

const activeTab = ref<TabKey>("issues");

interface IssueFormArgument {
  body: string;
  sources: string;
}

const newIssueForm = reactive({
  title: "",
  slug: "",
  url: "",
  description: "",
  more_info: "",
  stances: {
    agree: [] as Array<number | string>,
    neutral: [] as Array<number | string>,
    disagree: [] as Array<number | string>,
  },
  proArguments: [] as IssueFormArgument[],
  conArguments: [] as IssueFormArgument[],
});

const issueUploadInput = ref<HTMLInputElement | null>(null);
const issueDirectoryInput = ref<HTMLInputElement | null>(null);
const argumentUploadInput = ref<HTMLInputElement | null>(null);

const issueImportState = reactive({
  fileName: "",
  issues: [] as AdminIssueImportItem[],
  arguments: [] as AdminArgumentUpsertPayload[],
  error: null as string | null,
});

interface MotionFileEntry {
  number: number;
  file: File;
  relativePath: string;
}

const issueDirectoryState = reactive({
  motions: [] as MotionFileEntry[],
  start: "",
  end: "",
  error: null as string | null,
});

const issueDirectoryBounds = computed(() => {
  if (!issueDirectoryState.motions.length) return null;
  const first = issueDirectoryState.motions[0].number;
  const last =
    issueDirectoryState.motions[issueDirectoryState.motions.length - 1].number;
  return {
    first,
    last,
    count: issueDirectoryState.motions.length,
  };
});

const issueDirectorySelectionPreview = computed(() => {
  if (!issueDirectoryState.motions.length) return null;
  const start = toMotionNumber(issueDirectoryState.start);
  const end = toMotionNumber(issueDirectoryState.end);
  if (start === null || end === null || end < start) return null;

  const motionByNumber = new Map(
    issueDirectoryState.motions.map((motion) => [motion.number, motion])
  );
  const selected: MotionFileEntry[] = [];
  for (let number = start; number <= end; number += 1) {
    const motion = motionByNumber.get(number);
    if (motion) {
      selected.push(motion);
    }
  }

  return {
    count: selected.length,
    requestedStart: start,
    requestedEnd: end,
    availableFirst: selected[0]?.number ?? null,
    availableLast: selected[selected.length - 1]?.number ?? null,
    missing: Math.max(0, end - start + 1 - selected.length),
  };
});

watch(
  () => [issueDirectoryState.start, issueDirectoryState.end],
  () => {
    issueDirectoryState.error = null;
  }
);

const argumentImportState = reactive({
  fileName: "",
  arguments: [] as AdminArgumentUpsertPayload[],
  error: null as string | null,
});

const issueImportExample = JSON.stringify(
  {
    issues: [
      {
        title: "Voorbeeld issue",
        slug: "voorbeeld-issue",
        description: "Korte omschrijving",
        more_info: "Uitgebreide toelichting",
        party_stances: { agree: [1], neutral: [], disagree: [2] },
        arguments: {
          pro: [
            {
              side: "pro",
              body: "Een voorbeeldargument voor het issue.",
              sources: ["https://voorbeeld.nl/bron"],
            },
          ],
          con: [],
        },
      },
    ],
    arguments: [
      {
        issue_slug: "voorbeeld-issue",
        side: "con",
        body: "Een extra tegenargument uit het importbestand.",
      },
    ],
  },
  null,
  2
);

const argumentImportExample = JSON.stringify(
  {
    arguments: [
      {
        issue_slug: "voorbeeld-issue",
        side: "pro",
        body: "Een argument gekoppeld via de slug.",
        sources: ["https://voorbeeld.nl/bron"],
      },
    ],
  },
  null,
  2
);

function createArgumentField(): IssueFormArgument {
  return { body: "", sources: "" };
}

newIssueForm.proArguments.push(createArgumentField());
newIssueForm.conArguments.push(createArgumentField());

function addArgumentField(side: "pro" | "con") {
  if (side === "pro") {
    newIssueForm.proArguments.push(createArgumentField());
  } else {
    newIssueForm.conArguments.push(createArgumentField());
  }
}

function removeArgumentField(side: "pro" | "con", index: number) {
  if (side === "pro") {
    newIssueForm.proArguments.splice(index, 1);
  } else {
    newIssueForm.conArguments.splice(index, 1);
  }
}

const isLoggedIn = computed(() => auth.isLoggedIn.value);
const adminEmail = ADMIN_EMAIL;
const adminUsername = ADMIN_USERNAME;

const hasIssueImport = computed(
  () =>
    issueImportState.issues.length > 0 || issueImportState.arguments.length > 0
);
const hasArgumentImport = computed(
  () => argumentImportState.arguments.length > 0
);

const selectedIssueId = ref<number | null>(null);
const argumentForm = reactive({
  side: "pro" as "pro" | "con",
  body: "",
  sources: "",
});

const editingIssueId = ref<number | null>(null);
const issueDraft = reactive({
  title: "",
  slug: "",
  url: "",
  description: "",
  more_info: "",
  stances: {
    agree: [] as Array<number | string>,
    neutral: [] as Array<number | string>,
    disagree: [] as Array<number | string>,
  },
});

const editingArgumentId = ref<number | null>(null);
const argumentEditDraft = reactive({
  side: "pro" as "pro" | "con",
  body: "",
  sources: "",
});

const editingPartyId = ref<number | null>(null);
const newPartyLogoInput = ref<HTMLInputElement | null>(null);
const editPartyLogoInput = ref<HTMLInputElement | null>(null);

const partyDraft = reactive({
  name: "",
  abbreviation: "",
  slug: "",
  logo_url: "",
  website_url: "",
  logoFile: null as File | null,
  logoPreview: null as string | null,
});

const newPartyForm = reactive({
  name: "",
  abbreviation: "",
  slug: "",
  logo_url: "",
  website_url: "",
  logoFile: null as File | null,
  logoPreview: null as string | null,
});

function setEditPartyLogoInput(el: HTMLInputElement | null) {
  editPartyLogoInput.value = el;
}

let newPartyLogoObjectUrl: string | null = null;
let editPartyLogoObjectUrl: string | null = null;

watch(
  () => newPartyForm.logo_url,
  (value) => {
    if (newPartyForm.logoFile) return;
    const trimmed = value.trim();
    newPartyForm.logoPreview = trimmed.length ? trimmed : null;
  }
);

watch(
  () => partyDraft.logo_url,
  (value) => {
    if (partyDraft.logoFile) return;
    const trimmed = value.trim();
    partyDraft.logoPreview = trimmed.length ? trimmed : null;
  }
);

function setNewPartyLogoFile(file: File | null) {
  if (newPartyLogoObjectUrl) {
    URL.revokeObjectURL(newPartyLogoObjectUrl);
    newPartyLogoObjectUrl = null;
  }

  newPartyForm.logoFile = file;

  if (file) {
    newPartyLogoObjectUrl = URL.createObjectURL(file);
    newPartyForm.logoPreview = newPartyLogoObjectUrl;
  } else {
    const trimmed = newPartyForm.logo_url.trim();
    newPartyForm.logoPreview = trimmed.length ? trimmed : null;
  }
}

function handleNewPartyLogoChange(event: Event) {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0] ?? null;
  setNewPartyLogoFile(file);
}

function clearNewPartyLogo() {
  setNewPartyLogoFile(null);
  if (newPartyLogoInput.value) {
    newPartyLogoInput.value.value = "";
  }
}

function setEditPartyLogoFile(file: File | null) {
  if (editPartyLogoObjectUrl) {
    URL.revokeObjectURL(editPartyLogoObjectUrl);
    editPartyLogoObjectUrl = null;
  }

  partyDraft.logoFile = file;

  if (file) {
    editPartyLogoObjectUrl = URL.createObjectURL(file);
    partyDraft.logoPreview = editPartyLogoObjectUrl;
  } else {
    const trimmed = partyDraft.logo_url.trim();
    partyDraft.logoPreview = trimmed.length ? trimmed : null;
  }
}

function handleEditPartyLogoChange(event: Event) {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0] ?? null;
  setEditPartyLogoFile(file);
}

function clearEditPartyLogo() {
  setEditPartyLogoFile(null);
  const input = editPartyLogoInput.value;
  if (input) {
    input.value = "";
  }
}

function resetNewPartyFormState() {
  newPartyForm.name = "";
  newPartyForm.abbreviation = "";
  newPartyForm.slug = "";
  newPartyForm.logo_url = "";
  newPartyForm.website_url = "";
  clearNewPartyLogo();
  newPartyForm.logoPreview = null;
}

function resetPartyDraftState() {
  partyDraft.name = "";
  partyDraft.abbreviation = "";
  partyDraft.slug = "";
  partyDraft.logo_url = "";
  partyDraft.website_url = "";
  clearEditPartyLogo();
  partyDraft.logoPreview = null;
}

const partyLookup = computed(() => {
  const map = new Map<number, string>();
  for (const party of parties.value) {
    map.set(party.id, party.abbreviation || party.name);
  }
  return map;
});

const selectedIssue = computed(() => {
  if (!selectedIssueId.value) return null;
  return (
    issues.value.find((issue) => issue.id === selectedIssueId.value) ?? null
  );
});

watch(
  () => issues.value,
  (currentIssues) => {
    if (!currentIssues.length) {
      selectedIssueId.value = null;
      return;
    }
    if (
      !selectedIssueId.value ||
      !currentIssues.some((issue) => issue.id === selectedIssueId.value)
    ) {
      selectedIssueId.value = currentIssues[0]?.id ?? null;
    }
  },
  { immediate: true }
);

async function initialize() {
  await loadIssues();
  await loadReports();
  await loadParties();
}

onMounted(async () => {
  await auth.restore();
  if (isAdmin.value) {
    await initialize();
  }
});

watch(
  () => isAdmin.value,
  async (value) => {
    if (value && !issues.value.length) {
      await initialize();
    }
  }
);

function toNumberArray(list: Array<number | string>): number[] {
  return Array.from(
    new Set(
      list.map((value) => Number(value)).filter((value) => !Number.isNaN(value))
    )
  );
}

function parseSources(input: string): string[] {
  return input
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter((line) => line.length > 0);
}

async function submitIssue() {
  const proArguments = newIssueForm.proArguments.filter(
    (argument) => argument.body.trim().length > 0
  );
  const conArguments = newIssueForm.conArguments.filter(
    (argument) => argument.body.trim().length > 0
  );

  const payload: AdminIssuePayload = {
    title: newIssueForm.title.trim(),
    slug: newIssueForm.slug.trim() || undefined,
    url: newIssueForm.url.trim() || null,
    description: newIssueForm.description.trim() || null,
    more_info: newIssueForm.more_info.trim() || null,
    party_stances: {
      agree: toNumberArray(newIssueForm.stances.agree),
      neutral: toNumberArray(newIssueForm.stances.neutral),
      disagree: toNumberArray(newIssueForm.stances.disagree),
    },
  };

  if (proArguments.length || conArguments.length) {
    payload.arguments = {
      pro: proArguments.map((argument) => ({
        side: "pro",
        body: argument.body.trim(),
        sources: parseSources(argument.sources),
      })),
      con: conArguments.map((argument) => ({
        side: "con",
        body: argument.body.trim(),
        sources: parseSources(argument.sources),
      })),
    };
  }

  const created = await createIssue(payload);
  if (!created) return;

  resetIssueForm();
  selectedIssueId.value = created.id;
}

function resetIssueForm() {
  newIssueForm.title = "";
  newIssueForm.slug = "";
  newIssueForm.url = "";
  newIssueForm.description = "";
  newIssueForm.more_info = "";
  newIssueForm.stances.agree = [];
  newIssueForm.stances.neutral = [];
  newIssueForm.stances.disagree = [];
  newIssueForm.proArguments.splice(
    0,
    newIssueForm.proArguments.length,
    createArgumentField()
  );
  newIssueForm.conArguments.splice(
    0,
    newIssueForm.conArguments.length,
    createArgumentField()
  );
}

function resetIssueDraft() {
  issueDraft.title = "";
  issueDraft.slug = "";
  issueDraft.url = "";
  issueDraft.description = "";
  issueDraft.more_info = "";
  issueDraft.stances.agree = [];
  issueDraft.stances.neutral = [];
  issueDraft.stances.disagree = [];
}

function startIssueEdit(issue: AdminIssue) {
  editingIssueId.value = issue.id;
  issueDraft.title = issue.title;
  issueDraft.slug = issue.slug ?? "";
  issueDraft.url = issue.url ?? "";
  issueDraft.description = issue.description ?? "";
  issueDraft.more_info = issue.more_info ?? "";
  issueDraft.stances.agree = [...issue.party_stances.agree];
  issueDraft.stances.neutral = [...issue.party_stances.neutral];
  issueDraft.stances.disagree = [...issue.party_stances.disagree];
}

function cancelIssueEdit() {
  editingIssueId.value = null;
  resetIssueDraft();
}

async function saveIssueEdit(issueId: number) {
  const payload: AdminIssuePayload = {
    title: issueDraft.title.trim(),
    slug: issueDraft.slug.trim() || undefined,
    url: issueDraft.url.trim() || null,
    description: issueDraft.description.trim() || null,
    more_info: issueDraft.more_info.trim() || null,
    party_stances: {
      agree: toNumberArray(issueDraft.stances.agree),
      neutral: toNumberArray(issueDraft.stances.neutral),
      disagree: toNumberArray(issueDraft.stances.disagree),
    },
  };

  if (!payload.title) return;

  const updated = await updateIssue(issueId, payload);
  if (!updated) return;

  cancelIssueEdit();
}

async function confirmDeleteIssue(issueId: number, title: string) {
  if (
    !window.confirm(
      `Weet je zeker dat je de kwestie "${title}" wilt verwijderen?`
    )
  )
    return;
  await deleteIssue(issueId);
  if (editingIssueId.value === issueId) {
    cancelIssueEdit();
  }
}

async function submitArgument() {
  if (!selectedIssueId.value) return;
  const payload: AdminArgumentPayload = {
    side: argumentForm.side,
    body: argumentForm.body.trim(),
    sources: parseSources(argumentForm.sources),
  };
  if (!payload.body) return;

  const created = await addArgument(selectedIssueId.value, payload);
  if (!created) return;

  argumentForm.body = "";
  argumentForm.sources = "";
}

function resetArgumentEditDraft() {
  argumentEditDraft.side = "pro";
  argumentEditDraft.body = "";
  argumentEditDraft.sources = "";
}

function startEditArgument(argument: AdminArgument) {
  editingArgumentId.value = argument.id;
  argumentEditDraft.side = argument.side;
  argumentEditDraft.body = argument.body;
  argumentEditDraft.sources = argument.sources.join("\n");
}

function cancelArgumentEdit() {
  editingArgumentId.value = null;
  resetArgumentEditDraft();
}

async function saveArgumentEdit(argumentId: number) {
  const payload: AdminArgumentUpsertPayload = {
    side: argumentEditDraft.side,
    body: argumentEditDraft.body.trim(),
    sources: parseSources(argumentEditDraft.sources),
  };

  if (!payload.body) return;

  const updated = await updateArgument(argumentId, payload);
  if (!updated) return;

  cancelArgumentEdit();
}

async function removeArgument(argumentId: number) {
  await removeArgumentApi(argumentId);
  if (editingArgumentId.value === argumentId) {
    cancelArgumentEdit();
  }
}

async function submitParty() {
  const payload: AdminPoliticalPartyPayload = {
    name: newPartyForm.name.trim(),
    abbreviation: newPartyForm.abbreviation.trim(),
    logo_url: newPartyForm.logo_url.trim() || null,
    website_url: newPartyForm.website_url.trim() || null,
  };
  const slug = newPartyForm.slug.trim();
  if (slug) {
    payload.slug = slug;
  }
  if (newPartyForm.logoFile) {
    payload.logo = newPartyForm.logoFile;
  }

  const created = await createParty(payload);
  if (!created) return;
  resetNewPartyFormState();
}

function startEdit(party: AdminPoliticalParty) {
  editingPartyId.value = party.id;
  partyDraft.name = party.name;
  partyDraft.abbreviation = party.abbreviation;
  partyDraft.slug = party.slug ?? "";
  partyDraft.logo_url = party.logo_url ?? "";
  partyDraft.website_url = party.website_url ?? "";
  partyDraft.logoFile = null;
  clearEditPartyLogo();
}

function cancelEdit() {
  editingPartyId.value = null;
  resetPartyDraftState();
}

async function saveParty(partyId: number) {
  const payload: Partial<AdminPoliticalPartyPayload> = {
    name: partyDraft.name.trim() || undefined,
    abbreviation: partyDraft.abbreviation.trim() || undefined,
    slug: partyDraft.slug.trim() || undefined,
    logo_url: partyDraft.logo_url.trim() || null,
    website_url: partyDraft.website_url.trim() || null,
  };
  if (partyDraft.logoFile) {
    payload.logo = partyDraft.logoFile;
  }
  const updated = await updateParty(partyId, payload);
  if (!updated) return;
  cancelEdit();
}

onBeforeUnmount(() => {
  if (newPartyLogoObjectUrl) {
    URL.revokeObjectURL(newPartyLogoObjectUrl);
    newPartyLogoObjectUrl = null;
  }
  if (editPartyLogoObjectUrl) {
    URL.revokeObjectURL(editPartyLogoObjectUrl);
    editPartyLogoObjectUrl = null;
  }
});

function triggerIssueUpload() {
  issueUploadInput.value?.click();
}

function triggerIssueDirectoryUpload() {
  issueDirectoryInput.value?.click();
}

function triggerArgumentUpload() {
  argumentUploadInput.value?.click();
}

function resetIssueDirectoryState() {
  issueDirectoryState.motions = [];
  issueDirectoryState.start = "";
  issueDirectoryState.end = "";
  issueDirectoryState.error = null;
}

function resetIssueImportState() {
  issueImportState.fileName = "";
  issueImportState.issues = [];
  issueImportState.arguments = [];
  issueImportState.error = null;
}

function resetArgumentImportState() {
  argumentImportState.fileName = "";
  argumentImportState.arguments = [];
  argumentImportState.error = null;
}

async function handleIssueDirectoryUpload(event: Event) {
  const input = event.target as HTMLInputElement;
  const files = Array.from(input.files ?? []);

  resetIssueDirectoryState();

  if (!files.length) {
    input.value = "";
    return;
  }

  const motions: MotionFileEntry[] = [];
  for (const file of files) {
    const relativePath = getFileRelativePath(file);
    const number = extractMotionNumber(relativePath);
    if (number === null) continue;
    motions.push({
      number,
      file,
      relativePath,
    });
  }

  if (!motions.length) {
    issueDirectoryState.error =
      "Geen motie JSON-bestanden gevonden in deze map.";
    input.value = "";
    return;
  }

  motions.sort((a, b) => a.number - b.number);
  issueDirectoryState.motions = motions;
  issueDirectoryState.start = String(motions[0].number);
  issueDirectoryState.end = String(motions[motions.length - 1].number);
  issueDirectoryState.error = null;

  input.value = "";
}

async function prepareIssueImportFromDirectory() {
  if (!issueDirectoryState.motions.length) return;

  const startNumber = toMotionNumber(issueDirectoryState.start);
  const endNumber = toMotionNumber(issueDirectoryState.end);

  if (startNumber === null || endNumber === null) {
    issueDirectoryState.error = "Voer een geldig start- en eindnummer in.";
    return;
  }

  if (endNumber < startNumber) {
    issueDirectoryState.error =
      "Het eindnummer moet groter of gelijk zijn aan het startnummer.";
    return;
  }

  const motionByNumber = new Map(
    issueDirectoryState.motions.map((motion) => [motion.number, motion])
  );
  const selected: MotionFileEntry[] = [];
  const missing: number[] = [];

  for (let number = startNumber; number <= endNumber; number += 1) {
    const motion = motionByNumber.get(number);
    if (motion) {
      selected.push(motion);
    } else {
      missing.push(number);
    }
  }

  if (!selected.length) {
    issueDirectoryState.error =
      "Geen moties gevonden voor het geselecteerde bereik.";
    return;
  }

  if (missing.length) {
    const missingLabel =
      missing.length > 5
        ? `${missing.slice(0, 5).join(", ")} en ${missing.length - 5} andere`
        : missing.join(", ");
    issueDirectoryState.error = `De moties ${missingLabel} ontbreken in de map.`;
    return;
  }

  const aggregatedIssues: AdminIssueImportItem[] = [];
  const aggregatedArguments: AdminArgumentUpsertPayload[] = [];

  for (const motion of selected) {
    try {
      const raw = await motion.file.text();
      const parsed = JSON.parse(raw) as unknown;
      const normalized = normalizeIssueImportPayload(parsed);
      aggregatedIssues.push(...normalized.issues);
      aggregatedArguments.push(...normalized.arguments);
    } catch (err) {
      const message = translateErrorMessage(err, {
        fallback: `Motie ${motion.number} kon niet worden gelezen.`,
      });
      issueDirectoryState.error = `Motie ${motion.number} kon niet worden gelezen: ${message}`;
      return;
    }
  }

  if (!aggregatedIssues.length && !aggregatedArguments.length) {
    issueDirectoryState.error =
      "Geen issues of argumenten gevonden in de geselecteerde moties.";
    return;
  }

  const firstNumber = selected[0].number;
  const lastNumber = selected[selected.length - 1].number;

  issueImportState.fileName = `Mapimport motie${firstNumber}-motie${lastNumber} (${selected.length} moties)`;
  issueImportState.issues = aggregatedIssues;
  issueImportState.arguments = aggregatedArguments;
  issueImportState.error = null;
  issueDirectoryState.error = null;
}

async function handleIssueFileUpload(event: Event) {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0];
  if (!file) return;

  issueImportState.fileName = file.name;
  issueImportState.error = null;
  issueImportState.issues = [];
  issueImportState.arguments = [];

  try {
    const raw = await file.text();
    const parsed = JSON.parse(raw) as unknown;
    const normalized = normalizeIssueImportPayload(parsed);
    if (!normalized.issues.length && !normalized.arguments.length) {
      throw new Error("Geen issues of argumenten gevonden in dit bestand.");
    }
    issueImportState.issues = normalized.issues;
    issueImportState.arguments = normalized.arguments;
  } catch (err) {
    issueImportState.error = translateErrorMessage(err, {
      fallback: "Het bestand kon niet worden gelezen.",
    });
    issueImportState.issues = [];
    issueImportState.arguments = [];
  } finally {
    input.value = "";
  }
}

async function handleArgumentFileUpload(event: Event) {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0];
  if (!file) return;

  argumentImportState.fileName = file.name;
  argumentImportState.error = null;
  argumentImportState.arguments = [];

  try {
    const raw = await file.text();
    const parsed = JSON.parse(raw) as unknown;
    const normalized = normalizeArgumentImportPayload(parsed);
    if (!normalized.length) {
      throw new Error("Geen argumenten gevonden in dit bestand.");
    }
    argumentImportState.arguments = normalized;
  } catch (err) {
    argumentImportState.error = translateErrorMessage(err, {
      fallback: "Het bestand kon niet worden gelezen.",
    });
    argumentImportState.arguments = [];
  } finally {
    input.value = "";
  }
}

async function saveIssueImport() {
  if (!hasIssueImport.value) return;

  if (issueImportState.issues.length) {
    const payload: AdminIssueImportPayload = {
      issues: issueImportState.issues,
    };
    const result = await importIssues(payload);
    if (!result) return;
  }

  if (issueImportState.arguments.length) {
    const payload: AdminArgumentImportPayload = {
      arguments: issueImportState.arguments,
    };
    const result = await importArguments(payload);
    if (!result) return;
  }

  resetIssueImportState();
  cancelIssueEdit();
  cancelArgumentEdit();
}

async function saveArgumentImport() {
  if (!hasArgumentImport.value) return;

  const payload: AdminArgumentImportPayload = {
    arguments: argumentImportState.arguments,
  };

  const result = await importArguments(payload);
  if (!result) return;

  resetArgumentImportState();
  cancelArgumentEdit();
}

function normalizeIssueImportPayload(raw: unknown): {
  issues: AdminIssueImportItem[];
  arguments: AdminArgumentUpsertPayload[];
} {
  const result = {
    issues: [] as AdminIssueImportItem[],
    arguments: [] as AdminArgumentUpsertPayload[],
  };

  if (Array.isArray(raw)) {
    if (raw.every(isArgumentPayload)) {
      result.arguments = raw as AdminArgumentUpsertPayload[];
    } else {
      result.issues = raw as AdminIssueImportItem[];
    }
    return result;
  }

  if (raw && typeof raw === "object") {
    const objectValue = raw as Record<string, unknown>;
    if (Array.isArray(objectValue.issues)) {
      result.issues = objectValue.issues as AdminIssueImportItem[];
    }
    if (Array.isArray(objectValue.arguments)) {
      result.arguments = objectValue.arguments as AdminArgumentUpsertPayload[];
    }
  }

  return result;
}

function normalizeArgumentImportPayload(
  raw: unknown
): AdminArgumentUpsertPayload[] {
  return normalizeIssueImportPayload(raw).arguments;
}

function getFileRelativePath(file: File): string {
  const candidate = (file as File & { webkitRelativePath?: string })
    .webkitRelativePath;
  return candidate ? candidate.replace(/\\/g, "/") : file.name;
}

function extractMotionNumber(relativePath: string): number | null {
  const normalized = relativePath.replace(/\\/g, "/");
  const segments = normalized.split("/");
  if (segments.length < 2) return null;
  const directoryName = segments[segments.length - 2];
  const fileName = segments[segments.length - 1];
  const directoryMatch = directoryName.match(/^motie(\d+)$/i);
  const fileMatch = fileName.match(/^motie(\d+)\.json$/i);
  if (!directoryMatch || !fileMatch) return null;
  const directoryNumber = Number.parseInt(directoryMatch[1], 10);
  const fileNumber = Number.parseInt(fileMatch[1], 10);
  if (!Number.isFinite(directoryNumber) || directoryNumber !== fileNumber)
    return null;
  return directoryNumber;
}

function toMotionNumber(value: string): number | null {
  const parsed = Number.parseInt(value, 10);
  if (!Number.isFinite(parsed) || parsed < 1) return null;
  return parsed;
}

function isArgumentPayload(value: unknown): value is Record<string, unknown> {
  if (!value || typeof value !== "object") return false;
  const candidate = value as Record<string, unknown>;
  return (
    typeof candidate.side === "string" && typeof candidate.body === "string"
  );
}

function resolvePartyList(ids: number[]): string {
  if (!ids.length) return "-";
  return ids.map((id) => partyLookup.value.get(id) ?? `#${id}`).join(", ");
}

function formatDate(value: string | null): string {
  if (!value) return "-";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleString("nl-NL", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

async function refreshAll() {
  if (!isAdmin.value) return;
  await initialize();
}
</script>
