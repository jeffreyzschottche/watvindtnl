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
      <h2>Neem contact op</h2>
      <form class="form">
        <label>
          Naam
          <input type="text" name="name" placeholder="Jouw naam" required />
        </label>
        <label>
          E-mailadres
          <input
            type="email"
            name="email"
            placeholder="naam@voorbeeld.nl"
            required
          />
        </label>
        <label>
          Bericht
          <textarea
            name="message"
            rows="5"
            placeholder="Waarmee kunnen we helpen?"
            required
          ></textarea>
        </label>
        <button type="submit" class="button">Verstuur bericht</button>
      </form>
    </section>
  </div>
</template>
