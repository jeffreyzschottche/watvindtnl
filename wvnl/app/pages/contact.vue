<template>
  <div class="page">
    <section class="page-hero">
      <div class="container narrow">
        <h1>Contact</h1>
        <p>
          Heb je vragen of wil je kennismaken met WVNL? Laat het ons weten via
          onderstaand formulier of neem direct contact met ons op.
        </p>
      </div>
    </section>

    <section class="container narrow page-section">
      <h2>Neem contact op</h2>
      <form class="form">
        <label>
          Naam
          <input type="text" name="name" placeholder="Jouw naam" required />
        </label>
        <label>
          E-mailadres
          <input type="email" name="email" placeholder="naam@voorbeeld.nl" required />
        </label>
        <label>
          Bericht
          <textarea name="message" rows="5" placeholder="Waarmee kunnen we helpen?" required></textarea>
        </label>
        <button type="submit" class="button">Verstuur bericht</button>
      </form>
    </section>

    <section class="section-light">
      <div class="container narrow page-section">
        <h2>Direct contact</h2>
        <p>Je kunt ons ook bereiken via:</p>
        <ul class="list">
          <li>Telefoon: <a href="tel:+31851234567">+31 85 123 45 67</a></li>
          <li>E-mail: <a href="mailto:hallo@wvnl.nl">hallo@wvnl.nl</a></li>
          <li>Adres: Stationsplein 1, 3511 ED Utrecht</li>
        </ul>
      </div>
    </section>
  </div>
</template>
