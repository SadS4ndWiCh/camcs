<img src=".github/frieren.gif" height="150">

# 🌺 Central Arcane Magic Control System (CAMCS)

As is widely known, using magic is very difficult, requiring skill in mana management, 
theoretical knowledge of deities, and an innate aptitude. So, the Central Arcane Magic Control System (CAMCS) was born.

A global system to support the individual in the control of *Arcane Magic*. Manages 
the control of *mana* and mediates communication with the gods in order to facilitate 
the use of *magic* by the individual.

> [!IMPORTANT]
> Due to the magic invocation process being mediated by the system, the number of 
> available spells is limited, with no freedom to explore the creation of new spells.

## 🧊 What can individual do?

#### Registration Ceremony

From the *registration ceremony*, the individual is marked with an insignia that 
defines their aptitude and their strong point.  Moreover, but no less importantly, 
it is through this ceremony that the individual is registered as an user of the system.

Currently insignias:

- Arcanum of Water
- Arcanum of Earth
- Arcanum of Fire
- Arcanum of Air
- Arcanum of Darkness
- Arcanum of Light

#### Pray

Through prayer, the individual can receive more knowledge from the gods, which 
consequently facilitates the process of mediation and increases the efficiency 
of magic.

#### Achievements

Each achievement that the individual has significantly contributes to their 
development. Achievements related to the gods somehow carry more weight.

#### Learn Spells

Through achievements or prayers, the individual can receive points offered by the 
system itself, which can be used to learn new spells or increase their attributes.

#### Release Spells

With their spells unleashed and enough mana, the individual can cast spells.

## 🎀 How it was possible?

Thanks to the great advances in the field of *Magical Science*, *Arcane Biology*, *Study of the Global Arcane Network* and *Ancient Technology*, this was made possible. You don't need to worry about understanding how it works behind the scenes, but know that through a safe and efficient process, even those with low or no aptitude will certainly be able to match a low to intermediate level mage.

## 🍂 How the system is structured?

### 🪭 Steps of each process

#### Ceremony Use Case

1. Individual starts the ceremony giving his name, soul id and a master code.
2. System validates the data and creates the individual entry.
	1. If individual's soul id already exists, the ceremony fails.
3. System generates the individual's insignia.
4. Individual receives its insignia.

#### Pray Use Case

1. Individual sends their prayer.
2. System analyzes the prayer and delivers a corresponding reward.
3. Individual receives the reward.

#### Learn Spells Use Case

1. Individual selects the spells that wants to learn.
2. System checks if spell is available to learn and if individual can do it.
	1. If the spell isn't available to learn, the learning process fail.
	2. If individual don't have the points enough, the learning process fail.
3. System decrease the individual points and apply all necessaries side-effects.
4. Individual receives the new spell.

#### Release Spell Use Case

1. Individual selects the spell that wants to release.
2. System checks if individual has mana enough.
	1. If individual's mana is low, the release spell process fail.
3. System gives the condensed knowledge to release.
4. Individual release the spell.

### 💍 Endpoints

#### Ceremony `POST` `/api/ceremony`

##### Body

| Field      | Data Type | Description                  |
| ---------- | --------- | ---------------------------- |
| `name`     | `string`  | The individual's name        |
| `soul`     | `string`  | The individual's soul        |
| `code`     | `string`  | The individual's master code |

##### Responses

| Status | Response                       |
| ------ | ------------------------------ |
| `201`  | `{ "access_token": "string" }` |
| `400`  | `Given soul already exists`    |

#### Ceremony Login `POST` `/api/ceremony/login`

##### Body

| Field      | Data Type | Description                  |
| ---------- | --------- | ---------------------------- |
| `soul`     | `string`  | The individual's soul        |
| `code`     | `string`  | The individual's master code |

##### Responses

| Status | Response                       |
| ------ | ------------------------------ |
| `200`  | `{ "access_token": "string" }` |
| `400`  | `Invalid sould or master code` |

#### Individual Profile `GET` `/api/individual/profile`

##### Headers

| Key              | Value            | Required |
| ---------------- | ---------------- | -------- |
| `Authentication` | `Bearer <token>` | `true`   |

##### Responses

| Status | Response                    |
| ------ | --------------------------- |
| `200`  | `{Individual}`              |
| `401`  | `You must be authenticated` |

#### Pray `POST` `/api/individual/pray`

##### Body

| Field      | Data Type | Description           |
| ---------- | --------- | --------------------- |
| `pray`     | `string`  | The individual's pray |

##### Headers

| Key              | Value            | Required |
| ---------------- | ---------------- | -------- |
| `Authentication` | `Bearer <token>` | `true`   |

##### Responses

| Status | Response                    |
| ------ | --------------------------- |
| `200`  | `OK`                        |
| `401`  | `You must be authenticated` |

#### Spells To Learn `GET` `/api/spells`

##### Headers

| Key              | Value            | Required |
| ---------------- | ---------------- | -------- |
| `Authentication` | `Bearer <token>` | `false`  |

> A rate limit of 1 request each 10 seconds will be present if `Authentication` header is omited.

##### Responses

| Status | Response            |
| ------ | ------------------- |
| `200`  | `Spell[]`           |
| `429`  | `Too Many Requests` |

#### Learn Spell `POST` `/api/spells/{id}/learn`

##### Headers

| Key              | Value            | Required |
| ---------------- | ---------------- | -------- |
| `Authentication` | `Bearer <token>` | `true`   |

##### Responses

| Status | Response                    |
| ------ | --------------------------- |
| `200`  | `OK`                        |
| `400`  | `Insufficient skill points` |
| `403`  | `Spell don't is available`  |

#### Release Spell `POST` `/api/individual/spells/{id}/release`

##### Headers

| Key              | Value            | Required |
| ---------------- | ---------------- | -------- |
| `Authentication` | `Bearer <token>` | `true`   |

##### Responses

| Status | Response                    |
| ------ | --------------------------- |
| `200`  | `OK`                        |
| `400`  | `Insufficient mana points`  |
| `404`  | `Spell not found`           |

### 🧶 Database

#### Individual

| Column     | Data Type                   | Description               |
| ---------- | --------------------------- | ------------------------- |
| `id`       | `PK` `INT` `AUTO_INCREMENT` | The individual's id       |
| `name`     | `VARCHAR(64)`               | The individual's name     |
| `soul`     | `VARCHAR(255)` `UNIQUE`     | The individual's soul     |
| `code`     | `VARCHAR(255)`              | The individual's code     |
| `insignia` | `INT`                       | The individual's insignia |

#### Individual Metadata

| Column          | Data Type                   | Description             |
| --------------- | --------------------------- | ----------------------- |
| `id`            | `PK` `INT` `AUTO_INCREMENT` | The metadata's id       |
| `individual_id` | `FK` `INT`                  | The metadata's owner id |
| `sp`            | `INT`                       | Total skill points      |
| `mp`            | `INT`                       | Total mana points       |
| `max_mp`        | `INT`                       | Max mana points         |
| `xp`            | `INT`                       | Total experience points |
| `level`         | `INT`                       | The individual's level  |

#### Spell


| Column          | Data Type                   | Description             |
| --------------- | --------------------------- | ----------------------- |
| `id`            | `PK` `INT` `AUTO_INCREMENT` | The spell's id          |
| `name`          | `VARCHAR(64)`               | The spell's name        |
| `type`          | `INT`                       | The spell's type        |
| `code`          | `VARCHAR(255)`              | The spell's code        |
| `price`         | `INT`                       | The spell's price in SP |
| `mana`          | `INT`                       | The spell's mana cost   |

#### Achievement

| Column          | Data Type                   | Description             |
| --------------- | --------------------------- | ----------------------- |
| `id`            | `PK` `INT` `AUTO_INCREMENT` | The achievement's id    |
| `name`          | `VARCHAR(64)`               | The achievement's name  |
| `type`          | `INT`                       | The achievement's type  |