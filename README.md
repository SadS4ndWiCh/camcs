<img src=".github/frieren.gif" height="150">

# 🌺 Central Arcane Magic Control System (CAMCS)

As is widely known, using magic is very difficult, requiring skill in mana management, 
theoretical knowledge of deities, and an innate aptitude. So, the Central Arcane Magic Control System (CAMCS) was born.

A global system to support the individual in the control of *Arcane Magic*. Manages 
the control of *mana* and mediates communication with the gods in order to facilitate 
the use of *magic* by the individual.

## 🛝 Summary

1. [What can individual do?](#-what-can-individual-do)
    1. [Registration Ceremony](#registration-ceremony)
    2. [Pray](#pray)
    3. [Achievement](#achievements)
    4. [Learn Spells](#learn-spells)
    5. [Release Spells](#release-spells)
    6. [Meditate](#meditate)
2. [How it was possible?](#-how-it-was-possible)
3. [Steps of each process](#-steps-of-each-process)
    1. [How the system is structured?](#-how-the-system-is-structured)
        1. [Ceremony Use Case](#ceremony-use-case)
        2. [Pray Use Case](#pray-use-case)
        3. [Learn Spell Use Case](#learn-spells-use-case)
        4. [Release Spell Use Case](#release-spell-use-case)
        5. [Meditate Use Case](#meditate-use-case)
    2. [Endpoints](#-endpoints)
        1. [Ceremony](#ceremony)
        2. [Login](#login)
        3. [Profile](#profile)
        4. [Pray](#pray-1)
        5. [List Spells](#list-spells)
        6. [Learn Spell](#learn-spell)
        7. [Meditate](#meditate)
    3. [Database](#-database)
4. [Progress of the system](#-progress-of-the-system)
4. [How to execute in my world](#-how-to-execute-in-my-world)

## 🧊 What can individual do?

#### Registration Ceremony

From the *registration ceremony*, the individual is marked with an insignia that 
defines their aptitude and their strong point.  Moreover, but no less importantly, 
it is through this ceremony that the individual is registered as an user of the system.

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

#### Meditate

Solve the lack of mana after releasing spells. You can increase by a little 
percent of your max mana in each meditation.

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

#### Meditate Use Case

1. Individual starts the meditation.
2. System calculate how many mp individual will receive.
3. Individual has mp increased.

### 💍 Endpoints

#### Ceremony

Do the registration to the system and receive the insignia.

<details>
<summary><h5>Request</h5></summary>

`POST` `/api/ceremony`

###### Request body

```json
{
    "name": "string",
    "soul": "string",
    "code": "string"
}
```

###### Request sample

```sh
$ curl -XPOST http://localhost:8080/api/ceremony \
       -H "Content-Type: application/json"       \
       -d '{ "name": "string", "soul": "string", "code": "string" }'
```
</details>

<details>
<summary><h5>Responses</h5></summary>

- `201` `Ceremony complete successfuly`
```json
{
    "message": "string",
    "access_token": "string"
}
```
- `400` `Invalid request data`
- `500` `Fail to complete ceremony`

</details>

#### Login

Do the login to the system given credentials of existing individual.

<details>
<summary><h5>Request</h5></summary>

`POST` `/api/ceremony/login`

###### Request body

```json
{
    "soul": "string",
    "code": "string"
}
```

###### Request sample

```sh
$ curl -XPOST http://localhost:8080/api/ceremony/login \
       -H "Content-Type: application/json"             \
       -d '{ "soul": "string", "code": "string" }'
```
</details>

<details>
<summary><h5>Responses</h5></summary>

- `201` `Logged successfuly`
```json
{
    "message": "string",
    "access_token": "string"
}
```
- `400` `Invalid request data`
- `401` `Individual's soul or code is invalid`

</details>

#### Profile

Get the currenlty logged individual's profile.

<details>
<summary><h5>Request</h5></summary>

`GET` `/api/individual/profile`

###### Request sample

```sh
$ curl -XGET http://localhost:8080/api/ceremony/profile \
       -H "Authorization: Bearer <token>"
```
</details>

<details>
<summary><h5>Responses</h5></summary>

##### Responses

- `200` `The profile`
```json
{
    "individual": {
        "id": "int",
        "name": "string",
        "soul": "string",
        "insignia": "string",
        "updated_at": "datetime",
        "created_at": "datetime"
    },
    "metadata": {
        "id": "int",
        "individual_id": "int",
        "sp": "int",
        "mp": "int",
        "max_mp": "int",
        "xp": "int",
        "level": "int",
        "updated_at": "datetime",
        "created_at": "datetime"
    }
}
```
- `400` `Invalid request data`
- `401` `Unauthorized`
- `404` `Individual with given ID was not found`
- `500` `Failed to grab individual's metadata`

</details>

#### Pray

Sends a prayer to the gods and receives `skill points` based in the worth of the prayer.

<details>
<summary><h5>Request</h5></summary>

`POST` `/api/individual/pray`

###### Request body

```json
{
    "prayer": "string"
}
```

###### Request sample

```sh
$ curl -XPOST http://localhost:8080/api/ceremony/pray \
       -H "Authorization: Bearer <token>"
       -d '{ "prayer": "string" }'
```
</details>

<details>
<summary><h5>Responses</h5></summary>

- `200` `Prayer successfuly complete`
- `400` `Invalid request data`
- `401` `Unauthorized`
- `500` `Failed to grab individual's metadata`
- `500` `Failed to complete prayer`

</details>

#### List Spells

List all spells.

<details>
<summary><h5>Request</h5></summary>

`GET` `/api/spells`

###### Request sample

```sh
$ curl -XGET http://localhost:8080/api/spells \
       -H "Authorization: Bearer <token>"
```
</details>

<details>
<summary><h5>Responses</h5></summary>

- `200` `List of Spells`
```json
[
    {
        "id": "int",
        "name": "string",
        "type": "string",
        "code": "string",
        "price": "int",
        "mana": "int",
        "updated_at": "datetime",
        "created_at": "datetime",
        "available": "boolean",
        "learned": "boolean"
    },
    ...
]
```
- `429` `Too many requests`
</details>

#### Learn Spell

Learn a new spell with the right amount of `skill points`.

<details>
<summary><h5>Request</h5></summary>

`POST` `/api/spells/{id}/learn`

###### Request params

- `id`: The spell id, e.g. `2`.

###### Request sample

```sh
$ curl -XPOST http://localhost:8080/api/spells/1/learn \
       -H "Authorization: Bearer <token>"
```
</details>
<details>
<summary><h5>Response</h5></summary>

- `200` `Spell learned successfuly`
- `400` `Spell isn't available to learn`
- `400` `Trying to learn a spell again`
- `400` `Insufficient skill point`
- `401` `Unauthorized`
- `404` `Spell not found`
- `500` `Failed to grab individual's metadata`
- `500` `Failed to complete learning`
</details>

#### Meditate

Increase the magic points through the meditation.

<details>
<summary><h5>Request</h5></summary>

`POST` `/api/individuals/meditate`

###### Request sample

```sh
$ curl -XPOST http://localhost:8080/api/individuals/meditate \
       -H "Authorization: Bearer <token>"
```
</details>
<details>
<summary><h5>Response</h5></summary>

- `200` `Meditation completed successfuly`
- `401` `Unauthorized`
- `500` `Failed to complete meditation`

</details>

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

## 🎎 Progress of the system

- [x] Authentication
    - [x] Registration Ceremony
    - [x] Login
- [ ] Individual
    - [x] Pray
    - [x] Meditate
    - [x] Learn Spells
    - [x] Release Spells
    - [ ] Status Attributes
    - [ ] Receive Achievements On Actions
- [ ] Achievements
    - [ ] Special Effects
- [ ] Leveling System
    - [ ] Gain XP On Actions
    - [ ] Increase Level Based On XP

## 🎡 How to execute in my world?

1. Clone repository from GitHub

```sh
$ git clone https://github.com/SadS4ndWiCh/camcs && cd camcs
```

2. Install dependencies

```sh
$ composer install
```

3. Setup enviroment variables

```sh
$ mv env .env
```

```env
database.default.hostname = 
database.default.database = 
database.default.username = 
database.default.password = 

JWT_SECRET_KEY =
```

4. Execute migrations

```sh
$ php spark migrate
```

5. Run

```sh
$ php spark serve
```