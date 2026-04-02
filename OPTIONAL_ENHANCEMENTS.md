# Optional Enhancements

This document outlines potential future enhancements for the Lavendr vending machine management system.

## 1. Machine Status Monitoring

**Description**: Track and display the real-time online/offline status of vending machines.

**Implementation Ideas**:
- Add `last_seen_at` timestamp column to `machines` table
- Implement heartbeat endpoint: machines ping the server every N minutes
- Machine marked as "offline" if no heartbeat received within threshold
- Visual indicator on machines list page (green dot = online, red dot = offline)
- Filter machines by status (online/offline)
- Alert system to notify manager when machines go offline

**Database Changes**:
```php
// Migration example
$table->timestamp('last_seen_at')->nullable();
$table->boolean('is_online')->default(true);
```

**Benefits**:
- Proactive maintenance - know when machines need attention
- Better inventory planning - avoid stocking offline machines
- Improved customer experience - address outages quickly

---

## 2. Low Stock Alerts

**Description**: Automated notifications when slot inventory falls below threshold.

**Implementation Ideas**:
- Add `low_stock_threshold` column to `slots` table (default: 5 units)
- Scheduled job checks stock levels every hour/day
- Dashboard widget showing slots needing restock
- Email notifications to manager
- Alert badge on slots list page for low-stock items
- Filter to show only low-stock slots

**Database Changes**:
```php
// Migration example
$table->integer('low_stock_threshold')->default(5);
$table->boolean('low_stock_alert_sent')->default(false);
```

**Benefits**:
- Never run out of popular items
- Proactive restocking
- Better inventory management
- Improved revenue - always have stock available

---

## 3. Additional Enhancements

### 3.1 Sales Analytics Dashboard
- Graph of daily/weekly/monthly sales trends
- Top-selling items per machine
- Revenue per machine/location
- Peak purchasing hours analysis

### 3.2 Employee Purchase History
- Employees can view their own purchase history
- Export to PDF/CSV
- Filter by date range, category, machine

### 3.3 Bulk Operations
- Bulk update slot prices
- Bulk restock multiple slots
- Bulk reset balances

### 3.4 Machine Maintenance Log
- Track maintenance activities per machine
- Schedule preventive maintenance
- Maintenance history and reports

### 3.5 Advanced Role Limits
- Time-based limits (e.g., different limits per meal period)
- Weekly/monthly limits in addition to daily
- Category-specific spending limits

### 3.6 Mobile App
- Employee mobile app for NFC-based purchasing
- Manager mobile app for monitoring machines
- Push notifications for low stock/offline machines

### 3.7 Reporting System
- Generate PDF reports for sales, inventory, balances
- Automated weekly/monthly email reports to management
- Export data to Excel/CSV for analysis

### 3.8 Multi-Location Support
- Group machines by location/building
- Location-level analytics
- Location-specific role limits

### 3.9 Inventory Management
- Track inventory movements (stock in/out)
- Supplier management
- Purchase orders for restocking

### 3.10 Employee Feedback System
- Allow employees to report issues or request products
- Rating system for products
- Wish list feature for new products
