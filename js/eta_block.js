( function( blocks, element ) {
	var el = element.createElement;

	blocks.registerBlockType( 
	'eta-blocks/attend', 
	{
		title: '勤怠打刻',
		icon: 'clock',
		description:'勤怠打刻画面を挿入します',
		category: 'common',
		keywords: ['勤怠打刻','attend'],
		edit: function(){
			return el(
				'div',
				{
					className: "attend-editer",
				},
				el (
					'p',
					{
						style: {
							backgroundColor: "#ff83003b",
							lineHeight: "60px",
							fontSize: "20px",
						}
					},
					'時計と打刻ボタンが表示されます'
				)
			);
		},
		save: function(){
			return el(
				'div',
				{
					className: "attend",
				},
				[
					el(
						'div',
						{
							id: "ClockDisplay",
							className: "clock",
							onload: "showTime()",
						},
					),
					el(
						'form',
						{
							id: "Stamping",
							className: "stamping",
						},
						el(
							'a',
							{
								className: "stamping-send wp-element-button wp-block-button__link",
								href: "javascript:void(0);",
								onclick: "stamping_time()",
							},
							'打刻',
						),
						el(
							'a',
							{
								className: "stamping-back wp-element-button wp-block-button__link",
								href: "#",
								onclick: "history.back()",
							},
							'戻る',
						),
						el(
							'a',
							{
								className: "stamping-back wp-element-button wp-block-button__link",
								href: "javascript:void(0);",
								onclick: "location.reload();",
							},
							'再読込',
						),
						el(
							'p',
							{
								id: "attendMsg",
								className: "attend-msg",
							},
							'',
						),
						el(
							'p',
							{
								className: "stamping-note-label",
							},
							'コメント',
						),
						el(
							'textarea',
							{
								id: "stamping-note",
								rows: "8",
								className: "stamping-note",
							},
						),
					),
				],
			);
		}
	}
	);

})( window.wp.blocks, window.wp.element );