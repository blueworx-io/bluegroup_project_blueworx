# Switching the client area on

Everything below is a setting, not a release. The code is in the plugin; none of
it does anything until these are filled in. Do them in this order — each one is
checkable on its own, so a mistake shows up next to its own step rather than
three steps later.

## 1. Connect SureCart

**SureCart → Settings.** The client area reads plans, subscriptions, invoices
and orders from SureCart's API. Until it is connected, every dashboard section
says "we could not load this" — which is deliberate, and is what you should see
before this step.

Use **test mode** while you are trying it out. SureCart keeps live and test
customers separately, so a test purchase will not appear on a live account and
vice versa. That is the most likely reason for an account looking empty when it
should not.

## 2. Put the real prices on the Pricing page

**Settings → BlueWorx Site → SureCart plan prices.**

Copy each plan's price ID out of SureCart — one for monthly, one for annual —
and paste it into the matching box. That plan then shows SureCart's price and
its Get Started button goes to checkout.

A plan left blank keeps the price built into the plugin and sends its button to
the contact form. So does a wrong ID, or SureCart being unreachable. The page
never breaks; it just stops being live.

**Check:** change a price in SureCart, reload `/pricing`. It should follow
within fifteen minutes, or immediately if you re-save the settings screen.

## 3. Send customers to their dashboard after they pay

**SureCart → the checkout form → success URL → `/dashboard`.**

This one lives in SureCart rather than in our settings, because SureCart owns
what happens after a payment. Without it a customer lands on SureCart's own
confirmation page instead of their dashboard.

## 4. Decide whether people can create their own account

**Settings → General → Membership → "Anyone can register".**

- **Off** (how it is now): `/register` invites the visitor to get in touch, and
  you create accounts yourself.
- **On**: anyone can create an account from `/register`.

Off is the safer default and is a real choice, not an oversight — turn it on
deliberately.

## 5. Check the way in

The Client Login link in the navigation goes to `/login` unless
**Settings → BlueWorx Site → Client Login link** says otherwise. Leave that
field empty unless you want it somewhere else.

## What to try, in order

1. Create yourself a client account (or register one, if you turned that on).
2. Sign out, sign back in from the nav's Client Login. You should land on
   `/dashboard`.
3. Buy a plan from `/pricing` using SureCart's test card. You should end up on
   the dashboard.
4. Check Subscriptions, Invoices and Orders all show that purchase.
5. Reset your password from `/login` and sign in with the new one. The email
   should link back to the site, never to `wp-login.php`.

## What is deliberately not here

- **No cancel button.** Changing or cancelling a plan is done by getting in
  touch. SureCart owns the billing state, and a cancel button that half-works on
  a live commercial site is worse than a clear route to a person.
- **No invoice PDFs.** SureCart does not provide one. Unpaid invoices offer a
  payment link instead, which is the thing a client actually wants there.
- **No checkout page of our own.** SureCart's checkout handles payment details,
  and rebuilding that is neither necessary nor wise.

## Only once all of the above works

Retiring SureDash, Elementor and UiCore (#34, #33, #35) comes last, and only
after a real test purchase has been through the new dashboard. Removal is the
step that cannot be undone quietly.
