# Vending Machine Application

A command-line vending machine application built in PHP, designed following **Domain-Driven Design (DDD)** principles. Fancy a soda? Keep reading.

---

## Setup & Run

### Prerequisites

- Docker and Docker Compose

### Run the application

1. Clone the repository:
   ```bash
   git clone https://github.com/xevifuster/vending_machine.git
   cd vending_machine
   ```

2. Build the Docker image:
   ```bash
   docker compose build
   ```

3. Run the application:
   ```bash
   docker compose run vending-machine
   ```

4. To stop the application, use the command `EXIT`.

The machine state is persisted in `app/storage/VM.json` via a Docker volume, so stock and coin changes are kept between executions.

### Run the tests

```bash
docker compose run --rm vending-machine php vendor/bin/phpunit test/
```

### Run app without Docker

Make sure you have:

- PHP 8.4+ installed
- composer

```bash
composer install
php app/ui/cli.php
```
---

## Use Cases & Commands

* **Insert coin**: `0.05`, `0.10`, `0.25`, `1`
* **Select item**: `GET-WATER`, `GET-JUICE`, `GET-SODA`
* **Refund coins**: `RETURN-COIN`
* **Reset machine**: `SERVICE`
* **Exit**: `EXIT`

---

## Architecture

The project follows a layered architecture inspired by **DDD**:

```
app/
 ├── application       # Use cases (business actions)
 ├── domain            # Core business logic and rules
 ├── infrastructure    # Persistence (Implemented JSON repository)
 ├── ui                # CLI interface
 ├── storage           # JSON state file
 └── tests             # Unit & integration tests
```

### Layers Overview

* **Domain**

    * Contains the core business logic
    * No dependency on infrastructure, use cases or UI

* **Application**

    * Orchestrates use cases
    * Acts as a bridge between UI and domain

* **Infrastructure**

    * Handles persistence using JSON files
    * Implements repository interfaces defined in the domain

* **UI (CLI)**

    * Entry point for user interaction
    * Parses commands and delegates all business logic to application layer

---

## Clarifications, decisions & trade-offs

### Persistence Choice

A JSON-based repository was chosen for simplicity, but I preferred to maintain consistency between executions rather than an In-memory persistence system. 
The design also allows replacing it with another persistence system (I.e a database) without affecting the domain layer.

### Separation of Concerns

* Business logic lives in the **domain**
* Use cases in **application**
* External concerns in **infrastructure**
* Input/output handled in **UI**

### Change Calculation

Change calculation is implemented as a domain service since it represents business logic that does not naturally belong to a single entity and can evolve independently.

The algorithm uses a **backtracking search** that tries all valid combinations of available coins to find the exact change. It prefers larger coins first (greedy ordering) and stops as soon as an exact solution is found.
If exact change cannot be returned due to coin constraints, the system returns the closest possible amount below the expected change.

### Application layer 

I have decided to have only one handler in a single Application service to avoid continuous system state load.
Probably, in a larger system, I would separate this into use-case handlers to keep the application services stateless.
But in this case I founded this way more optimal.

Also, the application service methods that implement use cases have been deliberately defined as private to ensure that they cannot be invoked directly from outside the application layer.
Instead, only the handler acts as the public entry point.

### The SERVICE command

The SERVICE command was intentionally designed to reset the machine to a predefined initial configuration instead of allowing arbitrary values for coins, items and quantities.
While allowing full customization via the SERVICE command would make the system more flexible, it would also require additional validation logic and increase the risk of invalid configurations.
Given the scope of the exercise, prioritizing a deterministic and controlled reset to a known state was considered a better trade-off.


---

