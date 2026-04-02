**Business Requirements Document (BRD)**

**Project Title:** Employee Purchase System via Access Card and Self-Service Vending Machines

1. **Project Overview:**

The goal of this project is to automate employee purchases from self-service vending
machines located at the workplace using their employee access cards (NFC or RFID).
The purchase process uses a virtual daily balance managed automatically through a
centralized admin panel.
The system does not rely on real money but rather on daily allocated points based on
employee classification. The project includes the development of a robust backend,

integration with card readers, vending machines, and a management panel with role-
based access control.

1. **Objectives:**
- Enable secure and seamless employee purchases from vending machines.
- Enforce internal policies regarding daily entitlements.
- Reduce waste and provide accurate consumption reports.
- Offer an easy and centralized platform for managing employee purchases.

1. **Target Audience:**
- All company employees (Managers, Regular Staff, Technicians, etc.).
- HR, Administration, and IT departments managing the system.

1. **Core Business Processes:**

**Purchase Workflow:**

1. The employee selects a product from the vending machine.
2. The employee taps their access card on the reader.
3. The machine sends the following data to the backend via API:
    1. Employee Card Number (User ID).
    2. Slot Number (Slot ID).
    3. Machine ID.
    4. Product Price.
4. The backend validates:
    1. Whether the card is linked to a valid employee.
    2. Whether the employee is within their daily quota.
    3. Whether the product category (juice/meal/snack) is allowed.
    4. Whether sufficient balance is available.
5. The system responds:
    1. **Success**: Dispense the product and deduct points.
    2. **Failure:** Reject the transaction and return a reason (e.g., quota
    exceeded, unauthorized product type, insufficient balance).

**Data Sent to Backend:**

| Field | Description |
| --- | --- |
| User ID | Employee card identifier |
| Machine ID | Identifier of the vending machine |
| Slot Number | Slot ID chosen inside the machine |
| Product Price | Price in points |
| Timestamp | Time of request |
1. **Daily Balance Management:**

**Option 1: Single Daily Recharge Based on Classification**

- Balance is automatically recharged at 12:00 AM.
- Balance is reset to zero before each recharge.
- Example:
    - **Manager**: 500 points/day.
    - **Regular Employee**: 300 points/day.
- Rules are applied automatically by a rule engine.

**Option 2: Dual Period Recharge (Breakfast + Lunch)**

- The day is divided into two time slots:
    - **Breakfast Period (e.g., 7:00 – 10:00 AM):** Points are added.
    - **Lunch Period (e.g., 12:00 – 3:00 PM):** Points are added again.
- Balance is reset before each period.
- Example:
    - Regular employee: 150 points for breakfast + 150 points for lunch.

1. **Purchase Rules by Classification:**

| Classification | Daily limits |
| --- | --- |
| Regular Employee | 1 Juice + 1 Meal + 1 Snack |
| Manager | 3 Juices + 2 Meals + 2 Snacks |
- Every transaction is logged.
- Attempts that exceed the quota are rejected.

1. **Slot Category Mapping:**

| Slot Range | Category |
| --- | --- |
| 1 - 10 | Juices |
| 11 - 30 | Meals |
| 31 - 40 | Snacks |
1. **Admin Panel Modules:**

**Employee Management**

- Add/Edit/Delete employee profiles.
- View individual purchase history.

| Field | Type | Description |
| --- | --- | --- |
| EmployeeID | Primary Key | Unique identifier for the employee |
| FullName | String | Full name of the employee |
| CardNumber | String (Unique) | Access card number (NFC/RFID) |
| ClassificationID | Foreign Key | Reference to employee type |
| Status | Enum (Active/Inactive) | Whether the employee is active |

**Classification Management**

- Define classifications (Manager, Employee, Supervisor...)
- Set daily balance per classification.
- Configure product access rules.

| Field | Type | Description |
| --- | --- | --- |
| ClassificationID | Primary Key | Unique identifier |
| Name | String | e.g., Manager, Regular Employee |
| DailyJuiceLimit | Integer | Max juices per day |
| DailyMealLimit | Integer | Max meals per day |
| DailySnackLimit | Integer | Max snacks per day |
| DailyPointLimit | Integer | Total points per day (optional override) |

**Balance & Recharge Settings**

- Choose recharge mode (daily or by period).
- View current balances.
- Set recharge timings.

**Vending Machine Management**

- Register vending machines and their locations.

| Field | Type | Description |
| --- | --- | --- |
| MachineID | Primary Key | Unique ID of the machine |
| Location | String | Physical location of the machine |
| Status | Enum | Active or Inactive |

**Slot Category Management**

- Classify each slot number.
- Map slots to product categories (juice, meal, snack).

| Field | Type | Description |
| --- | --- | --- |
| SlotID | Primary Key | Unique slot identifier |
| MachineID | Foreign Key | The vending machine this slot belongs to |
| Category | Enum (Juice, Meal, Snack) | Type of item |
| Price | Integer | Cost in points |

**Reports & Analytics**

- Daily/Weekly/Monthly Reports:
    - Transactions per employee.
    - Most consumed products.
    - Balance usage.
    - Rejection reasons and statistics.

| Field | Type | Description |
| --- | --- | --- |
| TransactionID | Primary Key | Unique transaction ID |
| EmployeeID | Foreign Key | Employee who initiated the transaction |
| MachineID | Foreign Key | Vending machine used |
| SlotID | Foreign Key | Specific slot (product) selected |
| PointsDeducted | Integer | Number of points deducted |
| TransactionTime | Timestamp | Date & time of transaction |
| Status | Enum (Success/Failure) | Transaction outcome |
| FailureReason | String (nullable) | Reason for failure (if any) |
