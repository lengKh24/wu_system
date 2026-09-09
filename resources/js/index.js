import Alpine from "alpinejs";
import { apiCrud } from "./uitilities/config";
import { table } from "./uitilities/gloabal";
import { formatDate, getValue } from "./uitilities/helper";
import { sidebar } from "./uitilities/sidebar";
import { initSidebarSearch } from "./uitilities/sidebar-search";

document.addEventListener("alpine:init", () => {
    Alpine.data("table", table);
    Alpine.data("sideMenu", sidebar);

    Alpine.magic("api", () => apiCrud);
    Alpine.magic("formatDate", () => formatDate);
    Alpine.magic("getValue", () => getValue);
});

if (!window.Alpine) {
    window.Alpine = Alpine;
    Alpine.start();
}

document.addEventListener("DOMContentLoaded", initSidebarSearch);
