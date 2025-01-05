( function( blocks, element ) {
	var el = element.createElement;

	blocks.registerBlockType( 
	'eta-view-blocks/attend-view', 
	{
		title: '直前の打刻履歴',
		icon: 'clock',
		description:'直前の打刻履歴直を挿入します',
		category: 'common',
		keywords: ['直前の打刻履歴','attend-view'],
		edit: function(){
			return el(
				'div',
				{
					className: "attend-view-editer",
				},
				el (
					'p',
					{
						style: {
							backgroundColor: "#00f7ff3b",
							lineHeight: "60px",
							fontSize: "20px",
						}
					},
					'直前の打刻履歴直が表示されます'
				)
			);
		},
		save: function(){
			return el(
				'div',
				{
					className: "attend-view",
				},
				[
					el(
						'p',
						{
							className: "stamping-note-label",
						},
						'直前の打刻(再読込すると反映されます)',
					),
					el(
						'div',
						{
							id: "AttendView",
							onload: "showAttend()",
						},
					),
				],
			);
		}
	}
	);

})( window.wp.blocks, window.wp.element );