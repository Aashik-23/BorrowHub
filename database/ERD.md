# BorrowHub data model

```mermaid
erDiagram
    users ||--o{ items : owns
    categories ||--o{ items : groups
    users ||--o{ rentals : borrows
    items ||--o{ rentals : requested_for
    rentals ||--o| reviews : receives
    rentals ||--o{ messages : contains
    users ||--o{ messages : sends
    users ||--o{ password_resets : recovers
    users {
        bigint id PK
        varchar email UK
        varchar password_hash
        enum role
        boolean active
        int session_version
    }
    items {
        bigint id PK
        bigint owner_id FK
        int category_id FK
        decimal daily_rate
        decimal deposit
        boolean available
        boolean hidden
        datetime deleted_at
    }
    rentals {
        bigint id PK
        bigint item_id FK
        bigint renter_id FK
        date start_date
        date end_date
        decimal daily_rate
        decimal total
        decimal deposit
        enum status
    }
    reviews {
        bigint id PK
        bigint rental_id FK,UK
        int rating
        varchar comment
    }
    messages {
        bigint id PK
        bigint rental_id FK
        bigint sender_id FK
        varchar body
    }
    contacts {
        bigint id PK
        varchar email
        varchar subject
        text body
        boolean resolved
    }
```

Contacts do not require an account. Rental prices are snapshots, not live references to listing prices. Soft-deleted listings retain their relations. Reviews have a unique rental key so a completed rental can receive only one borrower review.
