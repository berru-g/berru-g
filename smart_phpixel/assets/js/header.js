// === CONFIGURATION ===
const config = {
  theme: 'system',
  duration: 0.18,
  opacity: 0.4,
  blur: 10,
  translate: 12,
  speed: 0.16,
  delay: true,
  triangle: true,
  show: false
};

// === FUNCTIONS POUR GÉRER LA CONFIGURATION ===
const update = () => {
  document.documentElement.dataset.theme = config.theme;
  document.documentElement.dataset.delay = config.delay;
  document.documentElement.dataset.triangle = config.triangle;
  document.documentElement.dataset.show = config.show;
  document.documentElement.style.setProperty('--layout-speed', config.speed);
  document.documentElement.style.setProperty('--duration', config.duration);
  document.documentElement.style.setProperty('--opacity', config.opacity);
  document.documentElement.style.setProperty('--blur', config.blur);
  document.documentElement.style.setProperty('--translate', config.translate);
};


// === FONCTIONS POUR LE PANEL DE CONTRÔLE ===
function togglePanel() {
  const panel = document.querySelector('.control-panel');
  panel.classList.toggle('collapsed');
}

function toggleGroup(group) {
  group.classList.toggle('collapsed');
}

// === FONCTION DE DRAGGABLE SIMPLIFIÉE ===
function makeDraggable(element, handle) {
  let isDragging = false;
  let offsetX = 0;
  let offsetY = 0;
  
  handle.style.cursor = 'move';
  
  handle.addEventListener('mousedown', startDrag);
  document.addEventListener('mousemove', drag);
  document.addEventListener('mouseup', stopDrag);
  
  function startDrag(e) {
    isDragging = true;
    const rect = element.getBoundingClientRect();
    offsetX = e.clientX - rect.left;
    offsetY = e.clientY - rect.top;
    element.style.transition = 'none';
  }
  
  function drag(e) {
    if (!isDragging) return;
    
    e.preventDefault();
    
    const x = e.clientX - offsetX;
    const y = e.clientY - offsetY;
    
    // Garder dans les limites de la fenêtre
    const maxX = window.innerWidth - element.offsetWidth;
    const maxY = window.innerHeight - element.offsetHeight;
    
    element.style.left = Math.min(Math.max(x, 0), maxX) + 'px';
    element.style.top = Math.min(Math.max(y, 0), maxY) + 'px';
    element.style.position = 'fixed';
  }
  
  function stopDrag() {
    isDragging = false;
    element.style.transition = '';
  }
  
  // Double-clic pour réinitialiser la position
  handle.addEventListener('dblclick', () => {
    element.style.left = '';
    element.style.top = '';
    element.style.position = '';
  });
}

// === DONNÉES DE L'ARBRE DE NAVIGATION ===
// === MISE À JOUR DES DONNÉES DE L'ARBRE DE NAVIGATION ===
const TREE_DATA = {
  label: "Navigation Berru-g",
  groups: [
    {
      title: "Navigation Principale",
      items: [
        { id: "home", label: "Accueil", href: "#", current: true },
        { id: "projects", label: "Projets", href: "#projects" },
        { id: "skills", label: "Compétences", href: "#skills" },
        { id: "experience", label: "Expérience", href: "#experience" }
      ]
    },
    {
      title: "Sections",
      items: [
        {
          id: "web-dev",
          label: "Développement Web",
          href: "#web-dev",
          items: [
            { id: "frontend", label: "Front-end", href: "https://codepen.io/h-lautre" },
            { id: "animations", label: "Animations", href: "#animations" },
            { id: "performance", label: "Performance", href: "#performance" }
          ]
        },
        {
          id: "design",
          label: "UI/UX Design",
          href: "#design",
          items: [
            { id: "ui-components", label: "Composants UI", href: "#ui-components" },
            { id: "prototyping", label: "Prototypage", href: "#prototyping" },
            { id: "design-systems", label: "Design Systems", href: "#design-systems" }
          ]
        },
        {
          id: "resources",
          label: "Ressources",
          href: "#resources",
          items: [
            { id: "articles", label: "Articles", href: "#articles" },
            { id: "tutorials", label: "Tutoriels", href: "#tutorials" },
            { id: "tools", label: "Outils", href: "#tools" },
            { id: "contact", label: "Contact", href: "#contact" }
          ]
        }
      ]
    }
  ]
};


// === GÉNÉRATION DE L'ARBRE HTML ===
function generateTreeHTML(data) {
  const processItems = (items, level = 1, parentId = null) => {
    const setSize = items.length;
    const htmlParts = [];

    items.forEach((item, index) => {
      const posInSet = index + 1;
      const hasChildren = item.items && item.items.length > 0;
      const itemId = `tree-item-${item.id}`;
      const groupId = hasChildren ? `tree-group-${item.id}` : null;

      let html = `<li role="none">`;
      html += `<a
        id="${itemId}"
        role="treeitem"
        href="${item.href || '#'}"
        tabindex="${item.current ? '0' : '-1'}"
        aria-level="${level}"
        aria-setsize="${setSize}"
        aria-posinset="${posInSet}"
        ${item.current ? 'aria-current="page"' : ''}
        ${hasChildren ? `aria-expanded="false" aria-owns="${groupId}"` : ''}
      >`;

      html += `<span>${item.label}</span>`;

      if (hasChildren) {
        html += `<span class="tree-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
          </svg>
        </span>`;
      }

      html += `</a>`;

      if (hasChildren) {
        html += `<div inert>`;
        html += `<ul id="${groupId}" role="group">`;
        html += processItems(item.items, level + 1, itemId);
        html += `</ul>`;
        html += `</div>`;
      }

      html += `</li>`;
      htmlParts.push(html);
    });

    return htmlParts.join('');
  };

  if (data.groups) {
    let treeHTML = `<ul role="tree" aria-label="${data.label}">`;

    data.groups.forEach((group, groupIndex) => {
      const groupId = `tree-group-toplevel-${groupIndex}`;
      treeHTML += `<li role="none" class="tree-group-container">`;
      treeHTML += `<ul role="group" id="${groupId}">`;
      treeHTML += processItems(group.items);
      treeHTML += `</ul>`;
      treeHTML += `</li>`;
    });

    treeHTML += `</ul>`;
    return treeHTML;
  }

  return `
    <ul role="tree" aria-label="${data.label}">
      ${processItems(data.items || [])}
    </ul>
  `;
}

// === COMPOSANT SIDEBAR-TREE ===
class SidebarTree extends HTMLElement {
  constructor() {
    super();
    this.currentFocus = null;
    this.nodeMap = new Map();
  }

  resetTabIndexes() {
    const treeItems = this.tree.querySelectorAll('[role="treeitem"]');
    treeItems.forEach(el => {
      el.setAttribute('tabindex', '-1');
    });
  }

  setFocusToItem(item, updateTabindex = true) {
    if (!item) return;

    if (updateTabindex) {
      this.resetTabIndexes();
      item.setAttribute('tabindex', '0');
    }

    item.focus();
    this.currentFocus = item;
  }

  isExpanded(item) {
    return item.getAttribute('aria-expanded') === 'true';
  }

  findParentTreeItem(childElement) {
    const parentGroup = childElement.closest('ul[role="group"][id]');
    if (parentGroup && parentGroup.id.startsWith('tree-group-') && !parentGroup.id.includes('toplevel')) {
      return this.querySelector(`[aria-owns="${parentGroup.id}"]`);
    }
    return null;
  }

  getGroupFromItem(item) {
    const groupId = item.getAttribute('aria-owns');
    return groupId ? document.getElementById(groupId) : null;
  }

  connectedCallback() {
    this.tree = this.querySelector('[role="tree"]');
    this.buildNodeMap();
    this.setupEventListeners();
    this.initializeFocus();
  }

  buildNodeMap() {
    const allTreeItems = this.querySelectorAll('[role="treeitem"]');

    allTreeItems.forEach(item => {
      const parentItem = this.findParentTreeItem(item);

      this.nodeMap.set(item.id, {
        id: item.id,
        level: parseInt(item.getAttribute('aria-level')),
        hasChildren: item.hasAttribute('aria-expanded'),
        parentId: parentItem ? parentItem.id : null,
        label: item.textContent.trim()
      });
    });
  }

  setupEventListeners() {
    this.tree.addEventListener('click', this.handleClick.bind(this));
    this.tree.addEventListener('keydown', this.handleKeydown.bind(this));
  }

  initializeFocus() {
    const currentItem = this.tree.querySelector('[aria-current="page"]');
    this.currentFocus = currentItem || this.tree.querySelector('[role="treeitem"]');

    if (currentItem) {
      this.ensureItemVisible(currentItem);
    }
  }

  handleClick(event) {
    const treeItem = event.target.closest('[role="treeitem"]');
    if (!treeItem) return;

    const icon = event.target.closest('.tree-icon');

    if (icon && treeItem.hasAttribute('aria-expanded')) {
      event.preventDefault();
      this.toggleExpanded(treeItem);
    } else if (!icon) {
      this.activateItem(treeItem);
    }
  }

  handleKeydown(event) {
    const treeItem = event.target.closest('[role="treeitem"]');
    if (!treeItem) return;

    switch (event.key) {
      case 'Enter':
      case ' ':
        event.preventDefault();
        this.activateItem(treeItem);
        treeItem.click();
        break;
      case 'ArrowDown':
        event.preventDefault();
        this.focusNextItem(treeItem);
        break;
      case 'ArrowUp':
        event.preventDefault();
        this.focusPreviousItem(treeItem);
        break;
      case 'ArrowRight':
        event.preventDefault();
        this.handleRightArrow(treeItem);
        break;
      case 'ArrowLeft':
        event.preventDefault();
        this.handleLeftArrow(treeItem);
        break;
      case 'Home':
        event.preventDefault();
        this.focusFirstItem();
        break;
      case 'End':
        event.preventDefault();
        this.focusLastItem();
        break;
      case '*':
        event.preventDefault();
        this.expandAllSiblings(treeItem);
        break;
      default:
        if (event.key.length === 1 && event.key.match(/[a-zA-Z]/)) {
          event.preventDefault();
          this.focusItemByFirstChar(event.key.toLowerCase());
        }
    }
  }

  toggleExpanded(item) {
    const wasExpanded = this.isExpanded(item);
    const group = this.getGroupFromItem(item);

    if (group) {
      const wrapper = group.parentElement;
      item.setAttribute('aria-expanded', !wasExpanded);

      if (wasExpanded) {
        wrapper.setAttribute('inert', '');
      } else {
        wrapper.removeAttribute('inert');
      }
    }
  }

  activateItem(item) {
    const currentItems = this.tree.querySelectorAll('[aria-current="page"]');
    currentItems.forEach(el => {
      el.removeAttribute('aria-current');
    });

    item.setAttribute('aria-current', 'page');
    this.resetTabIndexes();
    item.setAttribute('tabindex', '0');
  }

  focusItem(item) {
    this.setFocusToItem(item);
  }

  focusNextItem(current) {
    const allVisible = this.getVisibleItems();
    const currentIndex = allVisible.indexOf(current);
    if (currentIndex < allVisible.length - 1) {
      this.focusItem(allVisible[currentIndex + 1]);
    }
  }

  focusPreviousItem(current) {
    const allVisible = this.getVisibleItems();
    const currentIndex = allVisible.indexOf(current);
    if (currentIndex > 0) {
      this.focusItem(allVisible[currentIndex - 1]);
    }
  }

  handleRightArrow(item) {
    if (item.hasAttribute('aria-expanded')) {
      if (!this.isExpanded(item)) {
        this.toggleExpanded(item);
      } else {
        const group = this.getGroupFromItem(item);
        const firstChild = group ? group.querySelector('[role="treeitem"]') : null;
        if (firstChild) {
          this.focusItem(firstChild);
        }
      }
    }
  }

  handleLeftArrow(item) {
    const nodeInfo = this.nodeMap.get(item.id);

    if (item.hasAttribute('aria-expanded') && this.isExpanded(item)) {
      this.toggleExpanded(item);
    } else if (nodeInfo.parentId) {
      const parent = document.getElementById(nodeInfo.parentId);
      if (parent) {
        this.focusItem(parent);
      }
    }
  }

  focusFirstItem() {
    const firstItem = this.tree.querySelector('[role="treeitem"]');
    this.focusItem(firstItem);
  }

  focusLastItem() {
    const allVisible = this.getVisibleItems();
    this.focusItem(allVisible[allVisible.length - 1]);
  }

  expandAllSiblings(item) {
    const nodeInfo = this.nodeMap.get(item.id);
    const parent = nodeInfo.parentId ?
      document.getElementById(nodeInfo.parentId).parentElement :
      this.tree;

    parent.querySelectorAll(':scope > li > [aria-expanded="false"]').forEach(sibling => {
      this.toggleExpanded(sibling);
    });
  }

  focusItemByFirstChar(char) {
    const allVisible = this.getVisibleItems();
    const current = document.activeElement;
    const currentIndex = allVisible.indexOf(current);

    for (let i = currentIndex + 1; i < allVisible.length; i++) {
      if (allVisible[i].textContent.toLowerCase().trim().startsWith(char)) {
        this.focusItem(allVisible[i]);
        return;
      }
    }

    for (let i = 0; i <= currentIndex; i++) {
      if (allVisible[i].textContent.toLowerCase().trim().startsWith(char)) {
        this.focusItem(allVisible[i]);
        return;
      }
    }
  }

  getVisibleItems() {
    const items = [];
    const walkTree = (element) => {
      const directItems = element.querySelectorAll(':scope > li > [role="treeitem"]');
      const groupItems = element.querySelectorAll(':scope > li > ul[role="group"] > li > [role="treeitem"]');
      const treeItems = [...directItems, ...groupItems];

      treeItems.forEach(item => {
        items.push(item);
        if (this.isExpanded(item)) {
          const group = this.getGroupFromItem(item);
          if (group) {
            walkTree(group);
          }
        }
      });
    };

    walkTree(this.tree);
    return items;
  }

  ensureItemVisible(item) {
    let parent = item.parentElement;
    while (parent && parent !== this.tree) {
      if (parent.getAttribute('role') === 'group') {
        const wrapper = parent.parentElement;
        if (wrapper && wrapper.hasAttribute('inert')) {
          const parentItem = this.tree.querySelector(`[aria-owns="${parent.id}"]`);
          if (parentItem && !this.isExpanded(parentItem)) {
            this.toggleExpanded(parentItem);
          }
        }
      }
      parent = parent.parentElement;
    }
  }

  filter(searchTerm) {
    const allItems = this.tree.querySelectorAll('[role="treeitem"]');

    if (!searchTerm || searchTerm.length < 3) {
      allItems.forEach(item => {
        item.removeAttribute('data-filtered');
        item.removeAttribute('data-search-match');
        item.removeAttribute('data-search-related');
      });
      this.tree.removeAttribute('data-filtering');

      const allExpandable = this.tree.querySelectorAll('[aria-expanded="true"]');
      allExpandable.forEach(item => {
        this.toggleExpanded(item);
      });

      const currentItem = this.tree.querySelector('[aria-current="page"]');
      if (currentItem) {
        this.ensureItemVisible(currentItem);
      }

      return 0;
    }

    this.tree.setAttribute('data-filtering', 'true');
    const term = searchTerm.toLowerCase();
    const matches = new Set();
    const relatedItems = new Set();

    allItems.forEach(item => {
      const text = item.textContent.toLowerCase();
      if (text.includes(term)) {
        matches.add(item);
        item.setAttribute('data-search-match', 'true');

        let parent = item.parentElement;
        while (parent && parent !== this.tree) {
          if (parent.getAttribute('role') === 'group') {
            const parentItem = this.tree.querySelector(`[aria-owns="${parent.id}"]`);
            if (parentItem) {
              relatedItems.add(parentItem);
              if (!this.isExpanded(parentItem)) {
                this.toggleExpanded(parentItem);
              }
            }
          }
          parent = parent.parentElement;
        }

        if (item.hasAttribute('aria-owns')) {
          const group = this.getGroupFromItem(item);
          if (group) {
            const descendants = group.querySelectorAll('[role="treeitem"]');
            descendants.forEach(desc => relatedItems.add(desc));
            if (!this.isExpanded(item)) {
              this.toggleExpanded(item);
            }
          }
        }
      }
    });

    allItems.forEach(item => {
      if (matches.has(item)) {
        item.removeAttribute('data-filtered');
        item.removeAttribute('data-search-related');
      } else if (relatedItems.has(item)) {
        item.removeAttribute('data-filtered');
        item.removeAttribute('data-search-match');
        item.setAttribute('data-search-related', 'true');
      } else {
        item.removeAttribute('data-search-match');
        item.removeAttribute('data-search-related');
        item.setAttribute('data-filtered', 'true');
      }
    });

    return matches.size;
  }
}

// === RECHERCHE DANS L'ARBRE ===
function setupSearch() {
  const searchInput = document.getElementById('tree-search');
  const sidebarTree = document.querySelector('sidebar-tree');

  function updateSearchAriaLabel(value, matches) {
    const baseLabel = 'Search navigation tree - Press slash to focus';

    if (!value || value.length < 3) {
      searchInput.setAttribute('aria-label', baseLabel);
    } else {
      searchInput.setAttribute('aria-label',
        matches > 0 ?
          `Search navigation tree - ${matches} items found - Press slash to focus` :
          'Search navigation tree - No items found - Press slash to focus');
    }
  }

  if (searchInput && sidebarTree) {
    searchInput.addEventListener('input', function(e) {
      const value = e.target.value.trim();
      const matches = sidebarTree.filter(value);
      updateSearchAriaLabel(value, matches);
    });

    searchInput.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        e.target.value = '';
        sidebarTree.filter('');
        updateSearchAriaLabel('', 0);
      }
    });

    document.addEventListener('keydown', function(e) {
      const tagName = e.target.tagName.toLowerCase();
      const isEditable = e.target.isContentEditable;
      const isInput = tagName === 'input' || tagName === 'textarea' || tagName === 'select';

      if (e.key === '/' && !isInput && !isEditable) {
        e.preventDefault();
        searchInput.focus();
        searchInput.select();
      }
    });
  }
}

// === GESTION DU POPOVER ===
function setupPopover() {
  const sidebar = document.querySelector('aside[popover]');
  
  function syncPopover() {
    const desktop = window.matchMedia('(min-width: 768px)').matches;
    if (sidebar) {
      sidebar.setAttribute('popover', desktop ? 'manual' : 'auto');
    }
  }
  
  if (sidebar) {
    window.addEventListener('resize', syncPopover);
    syncPopover();
  }
}

// === INITIALISATION ===
document.addEventListener('DOMContentLoaded', function() {
  // Initialiser la configuration
  update();
  
  // Créer le panneau de contrôle
  //createControlPanel();
  
  // Générer l'arbre de navigation
  const treeContainer = document.querySelector('sidebar-tree');
  if (treeContainer) {
    treeContainer.innerHTML = generateTreeHTML(TREE_DATA);
  }
  
  // Enregistrer le custom element
  if (!customElements.get('sidebar-tree')) {
    customElements.define('sidebar-tree', SidebarTree);
  }
  
  // Configurer la recherche
  setupSearch();
  
  // Configurer le popover
  setupPopover();
});