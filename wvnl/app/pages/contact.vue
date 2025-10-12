<template>
  <div class="page">
    <section class="page-hero">
      <div class="container narrow">
        <h1>Contact</h1>
        <p>
          Wil je zelf een motie aanvragen? Heb je ideeën? Of wil je meer weten
          over WDNL?
        </p>
        <p>Laat het ons weten via onderstaand formulier.</p>
      </div>
    </section>

    <section class="container narrow">
      <form class="form form-card contact-form">
        <header class="form-header">
          <h2>Neem contact op</h2>
          <p>We reageren zo snel mogelijk op je bericht.</p>
        </header>

        <label>
          <span>Naam</span>
          <input type="text" name="name" placeholder="Jouw naam" required />
        </label>

        <label>
          <span>E-mailadres</span>
          <input
            type="email"
            name="email"
            placeholder="naam@voorbeeld.nl"
            required
          />
        </label>

        <label>
          <span>Bericht</span>
          <textarea
            name="message"
            rows="5"
            placeholder="Waarmee kunnen we helpen?"
            required
          ></textarea>
        </label>

        <div class="form-actions">
          <button type="submit" class="button">Verstuur bericht</button>
        </div>
      </form>
    </section>
  </div>
</template>
