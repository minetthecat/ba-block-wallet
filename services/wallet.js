window.addEventListener('load', () => {
	
	/* HERO */
	const pageHeroBreadcrumb = document.getElementById('page-hero-breadcrumb');
	const pageHeroTitle = document.getElementById('page-hero-title');
	const pageHeroText = document.getElementById('page-hero-text');

	pageHeroBreadcrumb.textContent = 'XXX';
	pageHeroTitle.textContent = 'XXX';
	pageHeroText.textContent = 'XXX';

/* SUBNAV */
const pageSubnavLinks = [
	{ href: '/wallet/', label: 'Přehled' },
	{ href: '/wallet/deposit', label: 'Vklad' },
	{ href: '/wallet/withdraw', label: 'Výběr' },
	{ href: '/wallet/wait', label: 'Čeká na schválení' },
	{ href: '/wallet/method', label: 'Platební metody' }
];

function pageSubnavRender() {
	const container = document.getElementById('page-subnav');
	if (!container) return;

	container.replaceChildren();

	pageSubnavLinks.forEach(item => {
		const link = document.createElement('a');
		link.href = item.href;
		link.textContent = item.label;
		link.classList.add('wallet-filter');

		if (item.href.replace(/\/+$/, '') === location.pathname.replace(/\/+$/, '')) {
			link.classList.add('wallet-filter');
			link.setAttribute('aria-current', 'page');
		}

		container.appendChild(link);
	});
}

pageSubnavRender();

});
