<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: Arial, sans-serif;
    }

    body {
        background-color: #121212;
        color: white;
    }

    header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 50px;
        background-color: #121212;
    }

    .title {
        font-size: 36px;
        font-weight: normal;
    }

    .profile-icon {
        width: 50px;
        height: 50px;
        background-color: #333;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: pointer;
    }

    .profile-icon svg {
        width: 24px;
        height: 24px;
        fill: #ccc;
    }

    .container {
        display: flex;
        padding: 20px;
        gap: 20px;
    }

    .sidebar {
        width: 200px;
        background-color: #1a1a1a;
        border-radius: 10px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .sidebar-item {
        padding: 15px 20px;
        font-size: 18px;
        text-decoration: none;
        color: white;
        display: block;
        transition: background-color 0.2s;
    }

    .sidebar-item:hover {
        background-color: #252535;
    }

    .sidebar-item.active {
        background-color: #2c2c3a;
    }

    .content {
        flex: 1;
        background-color: #1a1a1a;
        border-radius: 10px;
        padding: 20px;
    }

    .content-header {
        font-size: 24px;
        margin-bottom: 20px;
    }

    .search-container {
        margin-bottom: 20px;
        display: flex;
        gap: 10px;
    }

    .search-input {
        flex: 1;
        padding: 10px 15px;
        border-radius: 5px;
        border: none;
        background-color: #252535;
        color: white;
    }

    .search-input::placeholder {
        color: #aaa;
    }

    .filter-select {
        padding: 10px 15px;
        border-radius: 5px;
        border: none;
        background-color: #252535;
        color: white;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        background-color: #1e1e2a;
        border-radius: 10px;
        overflow: hidden;
    }

    th {
        background-color: #252535;
        padding: 15px;
        text-align: left;
        font-weight: normal;
        cursor: pointer;
        position: relative;
    }

    th:hover {
        background-color: #2a2a40;
    }

    th::after {
        content: "";
        position: absolute;
        right: 10px;
        opacity: 0.5;
    }

    th.sort-asc::after {
        content: "↑";
    }

    th.sort-desc::after {
        content: "↓";
    }

    td {
        padding: 15px;
        border-bottom: 1px solid #2a2a3a;
    }

    tr:last-child td {
        border-bottom: none;
    }

    tr:hover td {
        background-color: #252535;
    }

    .status-paid,
    .status-unpaid,
    .status-overdue {
        color: white;
    }

    .calendar-icon {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 60px;
        height: 60px;
        background-color: #333;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .calendar-icon:hover {
        background-color: #444;
    }

    .calendar-icon svg {
        width: 30px;
        height: 30px;
        fill: white;
    }

    .indicator {
        position: fixed;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 10px;
        height: 10px;
        background-color: #ccc;
        border-radius: 50%;
        margin: 10px;
    }

    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 20px;
        gap: 10px;
    }

    .pagination-button {
        padding: 8px 15px;
        background-color: #252535;
        border: none;
        border-radius: 5px;
        color: white;
        cursor: pointer;
    }

    .pagination-button:hover {
        background-color: #2a2a40;
    }

    .pagination-button.active {
        background-color: #3a3a50;
    }

    .loading {
        text-align: center;
        padding: 20px;
        font-size: 18px;
        color: #aaa;
    }

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        z-index: 1000;
        justify-content: center;
        align-items: center;
    }

    .modal-content {
        background-color: #1a1a1a;
        padding: 20px;
        border-radius: 10px;
        width: 80%;
        max-width: 600px;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .modal-title {
        font-size: 24px;
    }

    .close-button {
        background: none;
        border: none;
        font-size: 24px;
        color: white;
        cursor: pointer;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-label {
        display: block;
        margin-bottom: 5px;
    }

    .form-input,
    .form-select {
        width: 100%;
        padding: 10px;
        border-radius: 5px;
        border: none;
        background-color: #252535;
        color: white;
    }

    .form-button {
        padding: 10px 20px;
        background-color: #3a3a50;
        border: none;
        border-radius: 5px;
        color: white;
        cursor: pointer;
        margin-top: 10px;
    }

    .form-button:hover {
        background-color: #4a4a60;
    }

    .action-buttons {
        display: flex;
        gap: 10px;
    }

    .edit-button,
    .delete-button {
        padding: 5px 10px;
        border: none;
        border-radius: 3px;
        cursor: pointer;
        font-size: 12px;
    }

    .edit-button {
        background-color: #2c2c3a;
        color: white;
    }

    .delete-button {
        background-color: #3a2c2c;
        color: white;
    }
</style>
