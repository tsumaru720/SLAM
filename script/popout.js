function popout(url) {
    window.open(url, 'popoutWindow', 'width=900, height=800, toolbar=no, status=no, menubar=no, scrollbars=yes');
}

if (window.name == 'popoutWindow') {
	document.write('<style>.nav, .nav2, .navspacer { display: none; }</style>');
}