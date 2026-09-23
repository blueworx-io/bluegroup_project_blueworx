# Switching the client area on

Everything below is a setting, not a release. Do them in this order — each one
is checkable on its own, so a mistake shows up next to its own step rather than
three steps later.

The client dashboard is the **BlueWorx Labs** plugin's, at
`/customer-dashboard/` (its Store pages feature). This plugin only adds the
Sales section to it — Commission and Quote Builder — for sales staff.

## 1. Connect SureCart

**SureCart → Settings.** The dashboard's plans, orders and invoices come from
SureCart. Use **test mode** while you are trying it out: SureCart keeps live and
test customers separately, so a test purchase will not appear on a live account.

## 2. Put the real prices on the Support page

**Settings → BlueWorx Site → SureCart plan prices.**

Copy each plan's price ID out of SureCart — one for monthly, one for annual —
and paste it into the matching box. That plan then shows SureCart's price and
its Get Started button goes to checkout.

A plan left blank keeps the price built into the plugin and sends its button to
the contact form. So does a wrong ID, or SureCart being unreachable. The page
never breaks; it just stops being live.

## 3. Send customers to their dashboard after they pay

**SureCart → the checkout form → success URL → `/customer-dashboard`.**

This one lives in SureCart, because SureCart owns what happens after a payment.

## 4. Signing in is SureCart's form

`/login` carries SureCart's own sign-in form, which covers a forgotten password
too. Accounts are created at checkout; to make one by hand, add the user under
**Users → Add New**.

Everyone, administrators included, lands on `/customer-dashboard` after signing
in.

## 5. Give a salesperson the Sales section

**Users → the person → Role → "BlueWorx: Sales Staff".**

They then see Commission and Quote Builder in the dashboard's sidebar. Nobody
else sees either. Administrators see them without being given the role.

The rates the calculator quotes (20% on Hosting and ClubHouse, 10% or 20% on
support depending on whether the package is worth £9,000 a year) are set in the
plugin's code, not in a setting — they are a business rule, not a preference.

The Quote Builder sizes a build in hours and quotes the package that covers it.
The same builder is on the public Support page.

**Settings → BlueWorx Site → Quote builder on Support** turns the public one
back to the plain hours slider. Worth knowing before you leave it on: it shows
visitors how we size work. Turning it off changes nothing for sales staff.

## 6. Check the way in

The Client Login link in the navigation goes to `/login` unless
**Settings → BlueWorx Site → Client Login link** says otherwise.

## What to try, in order

1. Create yourself a client account under Users → Add New.
2. Sign out, sign back in from the nav's Client Login. You should land on
   `/customer-dashboard`.
3. Buy a plan from `/support` using SureCart's test card. You should end up on
   the dashboard, with the purchase in it.
4. Make a Sales Staff user and sign in as them: Commission and Quote Builder
   should be in the sidebar. A plain client should not see them.
