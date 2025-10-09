<template>
  <div class="min-h-screen bg-slate-100 p-6 md:p-10">
    <div class="mx-auto max-w-6xl space-y-8">
      <header class="flex flex-col gap-3 rounded-xl bg-white p-6 shadow-sm md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-2xl font-bold text-slate-900">WatVindt.nl CMS</h1>
          <p class="text-sm text-slate-500">
            Beheer kwesties, argumenten, meldingen en politieke partijen vanuit één overzicht.
          </p>
        </div>
        <div class="rounded-lg bg-slate-100 px-4 py-3 text-sm text-slate-600">
          <p class="font-semibold text-slate-700">Ingelogd als</p>
          <p v-if="isLoggedIn" class="truncate">{{ auth.user.value?.email }}</p>
          <p v-else class="truncate">Niet ingelogd</p>
        </div>
      </header>

      <section v-if="!isLoggedIn" class="rounded-xl bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-800">Aanmelden vereist</h2>
        <p class="mt-2 text-slate-600">
          Log in met het beheerdersaccount ({{ adminEmail }}) om toegang te krijgen tot het CMS.
        </p>
        <NuxtLink
          to="/login"
          class="mt-4 inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
        >
          Naar inloggen
        </NuxtLink>
      </section>

      <section
        v-else-if="!isAdmin"
        class="rounded-xl bg-white p-6 shadow-sm"
      >
        <h2 class="text-lg font-semibold text-slate-800">Geen toegang</h2>
        <p class="mt-2 text-slate-600">
          Dit CMS is alleen beschikbaar voor het administratieve account ({{ adminEmail }} / {{ adminUsername }}).
        </p>
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
                  : 'text-slate-600 hover:bg-slate-100'
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
              <span class="inline-block h-2 w-2 rounded-full" :class="loading ? 'bg-amber-500' : 'bg-emerald-500'"></span>
              {{ loading ? "Bezig met laden" : "Gegevens up-to-date" }}
            </button>
            <span v-if="error" class="flex items-center gap-2 rounded-lg bg-red-50 px-3 py-2 font-medium text-red-700">
              <span>⚠️</span>
              <span>{{ error }}</span>
            </span>
          </div>
        </div>

        <div v-if="activeTab === 'issues'" class="space-y-6">
          <section class="rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-4 flex items-center justify-between">
              <div>
                <h2 class="text-lg font-semibold text-slate-800">Nieuwe kwestie toevoegen</h2>
                <p class="text-sm text-slate-500">
                  Vul de basisgegevens in en voeg optioneel alvast argumenten toe.
                </p>
              </div>
            </header>
            <form class="grid gap-4" @submit.prevent="submitIssue">
              <div class="grid gap-2">
                <label class="text-sm font-medium text-slate-700">Titel</label>
                <input v-model="newIssueForm.title" type="text" required class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none" />
              </div>
              <div class="grid gap-2 md:grid-cols-2 md:gap-4">
                <div class="grid gap-2">
                  <label class="text-sm font-medium text-slate-700">Slug (optioneel)</label>
                  <input v-model="newIssueForm.slug" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none" />
                </div>
                <div class="grid gap-2">
                  <label class="text-sm font-medium text-slate-700">Bron URL (optioneel)</label>
                  <input v-model="newIssueForm.url" type="url" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none" />
                </div>
              </div>
              <div class="grid gap-2">
                <label class="text-sm font-medium text-slate-700">Korte beschrijving</label>
                <textarea v-model="newIssueForm.description" rows="3" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"></textarea>
              </div>
              <div class="grid gap-2">
                <label class="text-sm font-medium text-slate-700">Uitgebreide toelichting</label>
                <textarea v-model="newIssueForm.more_info" rows="4" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"></textarea>
              </div>

              <div class="grid gap-4 md:grid-cols-3">
                <div class="grid gap-2">
                  <label class="text-sm font-semibold text-slate-700">Voor (agree)</label>
                  <select
                    v-model="newIssueForm.stances.agree"
                    multiple
                    class="h-32 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                  >
                    <option v-for="party in parties" :key="party.id" :value="party.id">{{ party.abbreviation || party.name }}</option>
                  </select>
                </div>
                <div class="grid gap-2">
                  <label class="text-sm font-semibold text-slate-700">Neutraal</label>
                  <select
                    v-model="newIssueForm.stances.neutral"
                    multiple
                    class="h-32 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                  >
                    <option v-for="party in parties" :key="party.id" :value="party.id">{{ party.abbreviation || party.name }}</option>
                  </select>
                </div>
                <div class="grid gap-2">
                  <label class="text-sm font-semibold text-slate-700">Tegen (disagree)</label>
                  <select
                    v-model="newIssueForm.stances.disagree"
                    multiple
                    class="h-32 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                  >
                    <option v-for="party in parties" :key="party.id" :value="party.id">{{ party.abbreviation || party.name }}</option>
                  </select>
                </div>
              </div>

              <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-lg border border-slate-200 p-4">
                  <header class="mb-3 flex items-center justify-between">
                    <div>
                      <h3 class="text-sm font-semibold text-slate-800">Voor argumenten</h3>
                      <p class="text-xs text-slate-500">Voeg argumenten toe zoals in de seeder.</p>
                    </div>
                    <button type="button" class="text-xs font-semibold text-slate-600 hover:text-slate-900" @click="addArgumentField('pro')">
                      + Argument
                    </button>
                  </header>
                  <div class="space-y-4">
                    <div
                      v-for="(argument, index) in newIssueForm.proArguments"
                      :key="`pro-${index}`"
                      class="rounded-lg bg-slate-50 p-3"
                    >
                      <label class="text-xs font-semibold text-slate-600">Inhoud</label>
                      <textarea v-model="argument.body" rows="3" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"></textarea>
                      <label class="mt-3 block text-xs font-semibold text-slate-600">Bronnen (één per regel)</label>
                      <textarea v-model="argument.sources" rows="2" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"></textarea>
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
                      <h3 class="text-sm font-semibold text-slate-800">Tegen argumenten</h3>
                      <p class="text-xs text-slate-500">Voeg tegenargumenten toe (optioneel).</p>
                    </div>
                    <button type="button" class="text-xs font-semibold text-slate-600 hover:text-slate-900" @click="addArgumentField('con')">
                      + Argument
                    </button>
                  </header>
                  <div class="space-y-4">
                    <div
                      v-for="(argument, index) in newIssueForm.conArguments"
                      :key="`con-${index}`"
                      class="rounded-lg bg-slate-50 p-3"
                    >
                      <label class="text-xs font-semibold text-slate-600">Inhoud</label>
                      <textarea v-model="argument.body" rows="3" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"></textarea>
                      <label class="mt-3 block text-xs font-semibold text-slate-600">Bronnen (één per regel)</label>
                      <textarea v-model="argument.sources" rows="2" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"></textarea>
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
                <h2 class="text-lg font-semibold text-slate-800">Bestaande kwesties</h2>
                <p class="text-sm text-slate-500">Bekijk alle kwesties inclusief argumenten en meldingen.</p>
              </div>
              <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                {{ issues.length }} kwesties
              </span>
            </header>
            <div v-if="!issues.length" class="rounded-lg border border-dashed border-slate-300 p-8 text-center text-slate-500">
              Nog geen kwesties gevonden. Voeg de eerste kwestie toe via het formulier hierboven.
            </div>
            <ul v-else class="space-y-4">
              <li
                v-for="issue in issues"
                :key="issue.id"
                class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"
              >
                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                  <div>
                    <h3 class="text-lg font-semibold text-slate-900">{{ issue.title }}</h3>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Slug: {{ issue.slug }}</p>
                    <p v-if="issue.url" class="mt-1 text-sm text-slate-500">Bron: {{ issue.url }}</p>
                    <p v-if="issue.description" class="mt-2 text-sm text-slate-600">{{ issue.description }}</p>
                  </div>
                  <button
                    type="button"
                    class="self-start rounded-md border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50"
                    @click="confirmDeleteIssue(issue.id, issue.title)"
                  >
                    Verwijderen
                  </button>
                </div>
                <div class="mt-4 grid gap-3 text-sm text-slate-600 md:grid-cols-3">
                  <div>
                    <p class="font-semibold text-slate-700">Argumenten</p>
                    <p>Voor: {{ issue.arguments.pro.length }}</p>
                    <p>Tegen: {{ issue.arguments.con.length }}</p>
                  </div>
                  <div>
                    <p class="font-semibold text-slate-700">Partijen</p>
                    <p class="text-xs text-slate-500">Voor: {{ resolvePartyList(issue.party_stances.agree) }}</p>
                    <p class="text-xs text-slate-500">Neutraal: {{ resolvePartyList(issue.party_stances.neutral) }}</p>
                    <p class="text-xs text-slate-500">Tegen: {{ resolvePartyList(issue.party_stances.disagree) }}</p>
                  </div>
                  <div>
                    <p class="font-semibold text-slate-700">Meldingen</p>
                    <p>Onjuist: {{ issue.reports.incorrect_information }}</p>
                    <p>Aanstootgevend: {{ issue.reports.offensive_information }}</p>
                    <p>Kwestie verkeerd: {{ issue.reports.issue_misworded }}</p>
                    <p>Argument verkeerd: {{ issue.reports.argument_misworded }}</p>
                  </div>
                </div>
              </li>
            </ul>
          </section>
        </div>

        <div v-if="activeTab === 'arguments'" class="space-y-6">
          <section class="rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
              <div>
                <h2 class="text-lg font-semibold text-slate-800">Argumenten beheren</h2>
                <p class="text-sm text-slate-500">Voeg argumenten toe aan een bestaande kwestie of verwijder ze.</p>
              </div>
              <select
                v-model.number="selectedIssueId"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none md:w-72"
              >
                <option disabled value="">Kies een kwestie</option>
                <option v-for="issue in issues" :key="issue.id" :value="issue.id">{{ issue.title }}</option>
              </select>
            </header>

            <div v-if="!issues.length" class="rounded-lg border border-dashed border-slate-300 p-8 text-center text-slate-500">
              Voeg eerst een kwestie toe voordat je argumenten kunt beheren.
            </div>

            <div v-else-if="!selectedIssue" class="rounded-lg border border-dashed border-slate-300 p-8 text-center text-slate-500">
              Selecteer een kwestie om argumenten te bekijken of toe te voegen.
            </div>

            <div v-else class="space-y-6">
              <div class="rounded-lg border border-slate-200 p-4">
                <h3 class="text-sm font-semibold text-slate-800">Nieuw argument toevoegen voor "{{ selectedIssue.title }}"</h3>
                <form class="mt-4 grid gap-4 md:grid-cols-2" @submit.prevent="submitArgument">
                  <div class="grid gap-2">
                    <label class="text-xs font-semibold text-slate-600">Zijde</label>
                    <select v-model="argumentForm.side" class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
                      <option value="pro">Voor</option>
                      <option value="con">Tegen</option>
                    </select>
                  </div>
                  <div class="grid gap-2 md:col-span-2">
                    <label class="text-xs font-semibold text-slate-600">Argumenttekst</label>
                    <textarea v-model="argumentForm.body" rows="4" required class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"></textarea>
                  </div>
                  <div class="grid gap-2 md:col-span-2">
                    <label class="text-xs font-semibold text-slate-600">Bronnen (één per regel)</label>
                    <textarea v-model="argumentForm.sources" rows="3" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"></textarea>
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
                  <h3 class="mb-3 text-sm font-semibold text-slate-800">Voor argumenten ({{ selectedIssue.arguments.pro.length }})</h3>
                  <div v-if="!selectedIssue.arguments.pro.length" class="rounded-lg border border-dashed border-slate-300 p-6 text-center text-xs text-slate-500">
                    Nog geen voor argumenten.
                  </div>
                  <ul v-else class="space-y-3">
                    <li
                      v-for="argument in selectedIssue.arguments.pro"
                      :key="argument.id"
                      class="rounded-lg bg-slate-50 p-3 text-sm text-slate-700"
                    >
                      <p class="whitespace-pre-line">{{ argument.body }}</p>
                      <ul v-if="argument.sources.length" class="mt-2 list-disc pl-5 text-xs text-slate-500">
                        <li v-for="(source, index) in argument.sources" :key="index">{{ source }}</li>
                      </ul>
                      <button
                        type="button"
                        class="mt-3 inline-flex items-center rounded-md border border-red-200 px-3 py-1 text-xs font-semibold text-red-600 hover:bg-red-50"
                        @click="removeArgument(argument.id)"
                      >
                        Verwijderen
                      </button>
                    </li>
                  </ul>
                </div>
                <div class="rounded-lg border border-slate-200 p-4">
                  <h3 class="mb-3 text-sm font-semibold text-slate-800">Tegen argumenten ({{ selectedIssue.arguments.con.length }})</h3>
                  <div v-if="!selectedIssue.arguments.con.length" class="rounded-lg border border-dashed border-slate-300 p-6 text-center text-xs text-slate-500">
                    Nog geen tegenargumenten.
                  </div>
                  <ul v-else class="space-y-3">
                    <li
                      v-for="argument in selectedIssue.arguments.con"
                      :key="argument.id"
                      class="rounded-lg bg-slate-50 p-3 text-sm text-slate-700"
                    >
                      <p class="whitespace-pre-line">{{ argument.body }}</p>
                      <ul v-if="argument.sources.length" class="mt-2 list-disc pl-5 text-xs text-slate-500">
                        <li v-for="(source, index) in argument.sources" :key="index">{{ source }}</li>
                      </ul>
                      <button
                        type="button"
                        class="mt-3 inline-flex items-center rounded-md border border-red-200 px-3 py-1 text-xs font-semibold text-red-600 hover:bg-red-50"
                        @click="removeArgument(argument.id)"
                      >
                        Verwijderen
                      </button>
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
                <h2 class="text-lg font-semibold text-slate-800">Meldingenoverzicht</h2>
                <p class="text-sm text-slate-500">Bekijk alle meldingen per kwestie op één plek.</p>
              </div>
              <button
                type="button"
                class="rounded-md border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100"
                @click="loadReports()"
              >
                Vernieuwen
              </button>
            </header>
            <div v-if="!reports.length" class="rounded-lg border border-dashed border-slate-300 p-8 text-center text-slate-500">
              Geen meldingen beschikbaar.
            </div>
            <div v-else class="overflow-x-auto">
              <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                  <tr>
                    <th class="px-4 py-3 text-left font-semibold">Kwestie</th>
                    <th class="px-4 py-3 text-left font-semibold">Onjuiste info</th>
                    <th class="px-4 py-3 text-left font-semibold">Aanstootgevend</th>
                    <th class="px-4 py-3 text-left font-semibold">Kwestie verkeerd</th>
                    <th class="px-4 py-3 text-left font-semibold">Argument verkeerd</th>
                    <th class="px-4 py-3 text-left font-semibold">Laatste update</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                  <tr v-for="issue in reports" :key="`report-${issue.id}`" class="bg-white">
                    <td class="px-4 py-3 font-medium text-slate-800">{{ issue.title }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ issue.reports.incorrect_information }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ issue.reports.offensive_information }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ issue.reports.issue_misworded }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ issue.reports.argument_misworded }}</td>
                    <td class="px-4 py-3 text-slate-500">{{ formatDate(issue.updated_at) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>
        </div>

        <div v-if="activeTab === 'parties'" class="space-y-6">
          <section class="rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-4">
              <h2 class="text-lg font-semibold text-slate-800">Nieuwe politieke partij</h2>
              <p class="text-sm text-slate-500">Voeg een partij toe met alle velden uit de seeder.</p>
            </header>
            <form class="grid gap-4 md:grid-cols-2" @submit.prevent="submitParty">
              <div class="grid gap-2">
                <label class="text-sm font-medium text-slate-700">Naam</label>
                <input v-model="newPartyForm.name" required type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none" />
              </div>
              <div class="grid gap-2">
                <label class="text-sm font-medium text-slate-700">Afkorting</label>
                <input v-model="newPartyForm.abbreviation" required type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none" />
              </div>
              <div class="grid gap-2">
                <label class="text-sm font-medium text-slate-700">Slug (optioneel)</label>
                <input v-model="newPartyForm.slug" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none" />
              </div>
              <div class="grid gap-2">
                <label class="text-sm font-medium text-slate-700">Logo URL (publieke route)</label>
                <input v-model="newPartyForm.logo_url" type="text" placeholder="/storage/logos/voorbeeld.png" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none" />
              </div>
              <div class="md:col-span-2 grid gap-2">
                <label class="text-sm font-medium text-slate-700">Website URL</label>
                <input v-model="newPartyForm.website_url" type="url" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none" />
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
                <h2 class="text-lg font-semibold text-slate-800">Bestaande partijen</h2>
                <p class="text-sm text-slate-500">Pas bestaande partijen aan en beheer logo's en links.</p>
              </div>
              <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                {{ parties.length }} partijen
              </span>
            </header>
            <div v-if="!parties.length" class="rounded-lg border border-dashed border-slate-300 p-8 text-center text-slate-500">
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
                      <label class="text-xs font-semibold text-slate-600">Naam</label>
                      <input v-model="partyDraft.name" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none" />
                    </div>
                    <div class="grid gap-2">
                      <label class="text-xs font-semibold text-slate-600">Afkorting</label>
                      <input v-model="partyDraft.abbreviation" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none" />
                    </div>
                  </div>
                  <div class="grid gap-2 md:grid-cols-3 md:gap-4">
                    <div class="grid gap-2">
                      <label class="text-xs font-semibold text-slate-600">Slug</label>
                      <input v-model="partyDraft.slug" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none" />
                    </div>
                    <div class="grid gap-2 md:col-span-2">
                      <label class="text-xs font-semibold text-slate-600">Logo URL</label>
                      <input v-model="partyDraft.logo_url" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none" />
                    </div>
                  </div>
                  <div class="grid gap-2">
                    <label class="text-xs font-semibold text-slate-600">Website URL</label>
                    <input v-model="partyDraft.website_url" type="url" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none" />
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
                <div v-else class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                  <div>
                    <h3 class="text-lg font-semibold text-slate-900">{{ party.name }}</h3>
                    <p class="text-xs uppercase tracking-wide text-slate-400">{{ party.abbreviation }} • {{ party.slug }}</p>
                    <p v-if="party.logo_url" class="mt-1 text-sm text-slate-500">Logo: {{ party.logo_url }}</p>
                    <p v-if="party.website_url" class="mt-1 text-sm text-slate-500">Website: {{ party.website_url }}</p>
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
import { computed, onMounted, reactive, ref, watch } from "vue";
import { useAdmin } from "~/composables/useAdmin";
import type { AdminArgumentPayload, AdminIssuePayload, AdminPoliticalPartyPayload } from "~/types/admin";

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
  deleteIssue,
  addArgument,
  removeArgument,
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

const selectedIssueId = ref<number | null>(null);
const argumentForm = reactive({
  side: "pro" as "pro" | "con",
  body: "",
  sources: "",
});

const editingPartyId = ref<number | null>(null);
const partyDraft = reactive({
  name: "",
  abbreviation: "",
  slug: "",
  logo_url: "",
  website_url: "",
});

const newPartyForm = reactive({
  name: "",
  abbreviation: "",
  slug: "",
  logo_url: "",
  website_url: "",
});

const partyLookup = computed(() => {
  const map = new Map<number, string>();
  for (const party of parties.value) {
    map.set(party.id, party.abbreviation || party.name);
  }
  return map;
});

const selectedIssue = computed(() => {
  if (!selectedIssueId.value) return null;
  return issues.value.find((issue) => issue.id === selectedIssueId.value) ?? null;
});

watch(
  () => issues.value,
  (currentIssues) => {
    if (!currentIssues.length) {
      selectedIssueId.value = null;
      return;
    }
    if (!selectedIssueId.value || !currentIssues.some((issue) => issue.id === selectedIssueId.value)) {
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
      list
        .map((value) => Number(value))
        .filter((value) => !Number.isNaN(value))
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
  const proArguments = newIssueForm.proArguments.filter((argument) => argument.body.trim().length > 0);
  const conArguments = newIssueForm.conArguments.filter((argument) => argument.body.trim().length > 0);

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
  newIssueForm.proArguments.splice(0, newIssueForm.proArguments.length, createArgumentField());
  newIssueForm.conArguments.splice(0, newIssueForm.conArguments.length, createArgumentField());
}

async function confirmDeleteIssue(issueId: number, title: string) {
  if (!window.confirm(`Weet je zeker dat je de kwestie "${title}" wilt verwijderen?`)) return;
  await deleteIssue(issueId);
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

async function submitParty() {
  const payload: AdminPoliticalPartyPayload = {
    name: newPartyForm.name.trim(),
    abbreviation: newPartyForm.abbreviation.trim(),
    slug: newPartyForm.slug.trim() || undefined,
    logo_url: newPartyForm.logo_url.trim() || null,
    website_url: newPartyForm.website_url.trim() || null,
  };
  const created = await createParty(payload);
  if (!created) return;
  newPartyForm.name = "";
  newPartyForm.abbreviation = "";
  newPartyForm.slug = "";
  newPartyForm.logo_url = "";
  newPartyForm.website_url = "";
}

function startEdit(party: { id: number; name: string; abbreviation: string; slug: string; logo_url: string | null; website_url: string | null }) {
  editingPartyId.value = party.id;
  partyDraft.name = party.name;
  partyDraft.abbreviation = party.abbreviation;
  partyDraft.slug = party.slug;
  partyDraft.logo_url = party.logo_url ?? "";
  partyDraft.website_url = party.website_url ?? "";
}

function cancelEdit() {
  editingPartyId.value = null;
  partyDraft.name = "";
  partyDraft.abbreviation = "";
  partyDraft.slug = "";
  partyDraft.logo_url = "";
  partyDraft.website_url = "";
}

async function saveParty(partyId: number) {
  const payload: Partial<AdminPoliticalPartyPayload> = {
    name: partyDraft.name.trim() || undefined,
    abbreviation: partyDraft.abbreviation.trim() || undefined,
    slug: partyDraft.slug.trim() || undefined,
    logo_url: partyDraft.logo_url.trim() || null,
    website_url: partyDraft.website_url.trim() || null,
  };
  const updated = await updateParty(partyId, payload);
  if (!updated) return;
  cancelEdit();
}

function resolvePartyList(ids: number[]): string {
  if (!ids.length) return "-";
  return ids
    .map((id) => partyLookup.value.get(id) ?? `#${id}`)
    .join(", ");
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
