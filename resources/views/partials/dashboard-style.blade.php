/* Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body.dark-theme {
    font-family: 'Segoe UI', sans-serif;
    background-color: #1E1E2F;
    color: #f0f0f0;
}

/* Header */
.header {
    background-color: #2C2C3E;
    padding: 15px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #444;
}

.header h1 {
    font-size: 1.5rem;
    color: #ffffff;
}

.menu-toggle {
    font-size: 1.4rem;
    background: none;
    border: none;
    color: #ffffff;
    cursor: pointer;
}

/* Avatar */
.avatar {
    width: 40px;
    height: 40px;
    background: #555;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.avatar-inner {
    width: 30px;
    height: 30px;
    background: #888;
    border-radius: 50%;
}

/* Container */
.container {
    display: flex;
    min-height: calc(100vh - 70px);
}

/* Sidebar */
.sidebar {
    width: 230px;
    background-color: #242438;
    padding-top: 20px;
    transition: transform 0.3s ease;
}

.sidebar.open {
    transform: translateX(0);
}

.sidebar-menu {
    list-style: none;
}

.sidebar-menu li {
    padding: 15px 25px;
}

.sidebar-menu li a {
    color: #ccc;
    text-decoration: none;
    display: block;
    transition: background 0.2s;
}

.sidebar-menu li.active a,
.sidebar-menu li a:hover {
    background-color: #3E3E55;
    color: #fff;
    border-radius: 5px;
}

/* Main Content */
.main-content {
    flex: 1;
    padding: 25px;
    background-color: #1E1E2F;
}

/* Charts */
.dashboard-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    margin-bottom: 40px;
}

.chart-box {
    background-color: #2C2C3E;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.chart-title {
    margin-bottom: 15px;
    font-size: 1.1rem;
    color: #ffffff;
}

/* Table */
.inventory-table-container {
    background-color: #2C2C3E;
    padding: 20px;
    border-radius: 10px;
    overflow-x: auto;
}

.inventory-table {
    width: 100%;
    border-collapse: collapse;
}

.inventory-table thead {
    background-color: #3E3E55;
}

.inventory-table th,
.inventory-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #444;
}

.inventory-table tbody tr:hover {
    background-color: #3C3C4F;
}

/* Floating Calendar Button */
.calendar-btn {
    position: fixed;
    bottom: 25px;
    right: 25px;
    width: 50px;
    height: 50px;
    background-color: #5A6A6F;
    border-radius: 50%;
    cursor: pointer;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.calendar-icon::before {
    content: "📅";
    font-size: 1.4rem;
}

/* Calendar Modal */
.calendar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(30,30,48,0.85);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 999;
}

.calendar-overlay.active {
    display: flex;
}

.calendar-modal {
    background-color: #2C2C3E;
    padding: 20px;
    border-radius: 10px;
    width: 90%;
    max-width: 600px;
}

.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    color: #fff;
}

.calendar-close {
    background: none;
    border: none;
    font-size: 1.4rem;
    color: #fff;
    cursor: pointer;
}

iframe {
    border-radius: 8px;
}

/* Responsive Fixes */
@media (max-width: 768px) {
    .sidebar {
        position: absolute;
        transform: translateX(-100%);
        height: 100%;
        z-index: 10;
    }

    .sidebar.open {
        transform: translateX(0);
    }

    .header {
        padding: 15px 20px;
    }

    .main-content {
        padding: 15px;
    }
}
