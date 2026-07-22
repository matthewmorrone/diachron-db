# Diachron DB
Database of Historical Sound Changes

A queryable, editable database of how languages' sounds shift over time — e.g. Latin *k*
softening into Italian *ch*, or the consonant shifts described by Grimm's Law on the way
from Proto-Indo-European to Germanic.

Live at **[matthewmorrone.com/projects/diachron-db](https://matthewmorrone.com/projects/diachron-db)**.

### Data model
+ **Transition** — a language relationship, e.g. "Latin → Sardinian"
+ **Pair** — one sound correspondence within a transition: a source segment that became a target segment
+ **Environment** — the phonological context that conditions a change (e.g. "before a vowel")
+ **Segment** / **Language** — the individual IPA symbols and named language varieties referenced above

The in-app "About" panel has the full notation key.

### Data source
Originally seeded from the [Index Diachronica](https://chridd.nfshost.com/diachronica/all)
via the parser in [`diachronica/diachronica.php`](diachronica/diachronica.php). The full parsed
dataset lives in [`diachronica/diachronica.sql`](diachronica/diachronica.sql) (802 languages,
2,880 segments, 14,649 pairs) — import that rather than the top-level `diachron.sql`, which is
just a handful of hand-entered example rows.

### Local setup
+ You'll need a local PHP + MySQL/MariaDB stack (e.g. [XAMPP](https://www.apachefriends.org/))
+ Clone this repo into your server's document root
+ Import [`diachronica/diachronica.sql`](diachronica/diachronica.sql) into a `diachron` database
  (or the smaller `diachron.sql` for a quick sample)
+ Add an `environment TEXT` column to `pairs` — the app reads/writes it (see `add_pair`/`query_pairs`
  in `mysql.php`) but neither SQL dump defines it yet
+ Create a `credentials.txt` in the project root defining `$hostname`, `$username`, `$password`,
  and `$database`:
  ```php
  <?php
  $hostname = "localhost";
  $username = "root";
  $password = "";
  $database = "diachron";
  ```
+ Navigate to the project in your browser

### Status
Publicly editable by design — anyone can add or correct a pair, no login required, in the spirit
of a community-maintained reference rather than a fixed one. Two known gaps if you're relying on
that: there's no edit history or rollback yet, so a bad edit isn't currently recoverable, and
`mysql.php`'s queries aren't parameterized. Both are tracked in
[issues](https://github.com/matthewmorrone/diachron-db/issues).

### Libraries
+ [Bootstrap 5](https://getbootstrap.com/)
+ [tagify](https://yaireo.github.io/tagify/)
+ [simple-keyboard](https://hodgef.com/simple-keyboard/)
+ [cytoscape.js](https://js.cytoscape.org/)
