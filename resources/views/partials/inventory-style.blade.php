<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: Arial, sans-serif;
    }

    body {
        background-color: #1a1a1a;
        color: white;
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 30px;
        background-color: #1a1a1a;
        border-bottom: 1px solid #333;
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .header h1 {
        font-size: 32px;
        font-weight: normal;
    }

    .avatar {
        width: 40px;
        height: 40px;
        background-color: #3a3f47;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .avatar-inner {
        width: 24px;
        height: 24px;
        background-color: #d3d3d3;
        border-radius: 50%;
        position: relative;
    }

    .avatar-inner::after {
        content: "";
        position: absolute;
        width: 20px;
        height: 10px;
        background-color: #d3d3d3;
        border-radius: 50% 50% 0 0;
        bottom: -5px;
        left: 2px;
    }

    .container {
        display: flex;
        height: calc(100vh - 81px);
    }

    .sidebar {
        width: 230px;
        background-color: #1e2126;
        padding: 20px 0;
        position: sticky;
        top: 81px;
        height: calc(100vh - 81px);
        overflow-y: auto;
    }

    .sidebar-menu {
        list-style: none;
    }

    .sidebar-menu li {
        padding: 15px 30px;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .sidebar-menu li:hover {
        background-color: #2c3038;
    }

    .sidebar-menu li.active {
        background-color: #3a3f47;
    }

    .main-content {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
    }

    .inventory-section {
        background-color: #23262d;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .section-title {
        font-size: 24px;
        margin-bottom: 20px;
    }

    .inventory-table {
        width: 100%;
        border-collapse: collapse;
    }

    .inventory-table th {
        text-align: left;
        padding: 12px 15px;
        background-color: #2a3038;
        color: #fff;
        font-weight: normal;
    }

    .inventory-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #3a3f47;
    }

    .inventory-table tr:last-child td {
        border-bottom: none;
    }

    .status {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 14px;
        text-align: center;
        min-width: 100px;
    }

    .status-low {
        background-color: #8B3A3A;
        color: white;
    }

    .status-high {
        background-color: #8B3A62;
        color: white;
    }

    .status-normal {
        background-color: #2E5E3A;
        color: white;
    }

    .calendar-btn {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 50px;
        height: 50px;
        background-color: #3a3f47;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        z-index: 10;
    }

    .calendar-icon {
        width: 24px;
        height: 24px;
        position: relative;
        border: 2px solid #fff;
        border-radius: 3px;
    }

    .calendar-icon::before {
        content: "";
        position: absolute;
        width: 4px;
        height: 4px;
        background-color: #fff;
        border-radius: 50%;
        top: 4px;
        left: 3px;
    }

    .calendar-icon::after {
        content: "";
        position: absolute;
        width: 4px;
        height: 4px;
        background-color: #fff;
        border-radius: 50%;
        top: 4px;
        right: 3px;
    }
</style>
