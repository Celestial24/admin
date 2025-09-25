document.addEventListener("DOMContentLoaded", () => {
    // Sample contract list (pwedeng galing backend via AJAX/Fetch)
    const contracts = [];
  
    // Elements
    const tableBody = document.getElementById("contractsTableBody");
    const countContracts = document.getElementById("countContracts");
    const auditTrail = document.getElementById("auditTrail");
    const searchInput = document.getElementById("contractSearch");
    const alertsCount = document.getElementById("alertsCount");
  
    // Render Contracts
    function renderContracts(list = contracts) {
      tableBody.innerHTML = "";
      list.forEach(c => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td class="px-3 py-2">${c.employee_id}</td>
          <td class="px-3 py-2">${c.employee_name}</td>
          <td class="px-3 py-2">${c.title}</td>
          <td class="px-3 py-2">${c.category}</td>
          <td class="px-3 py-2">${c.party}</td>
          <td class="px-3 py-2">${c.expiry}</td>
          <td class="px-3 py-2">${c.risk}</td>
          <td class="px-3 py-2">${c.confidence}%</td>
          <td class="px-3 py-2">
            <button class="px-2 py-1 text-sm bg-indigo-600 text-white rounded">View</button>
          </td>
        `;
        tableBody.appendChild(tr);
      });
      countContracts.textContent = list.length;
    }
  
    // Render Audit Trail
    function renderAuditTrail(entries) {
      auditTrail.innerHTML = "";
      entries.forEach(log => {
        const li = document.createElement("li");
        li.textContent = `${log.time} - ${log.action}`;
        auditTrail.appendChild(li);
      });
    }
  
    // Search filter
    searchInput?.addEventListener("input", e => {
      const term = e.target.value.toLowerCase();
      const filtered = contracts.filter(c =>
        Object.values(c).some(v => String(v).toLowerCase().includes(term))
      );
      renderContracts(filtered);
    });
  
    // Alerts counter example
    function updateAlerts(count) {
      alertsCount.textContent = count;
    }
  
    // Initialize
    renderContracts();
    renderAuditTrail([]);
    updateAlerts(0);
  });
  