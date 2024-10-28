import React, {Component} from 'react';
import PropTypes from 'prop-types';
import moment from 'moment';
import formatPHP from '../../helpers/formatPHP';
import {_e} from '../../helpers/globalHelpers';
import {getEventUrl} from '../../helpers/eventsHelpers';
import Scroll from 'react-scroll';
import { Backdrop, SingleEventWrapper } from './styles';

export default class SingleEvent extends Component {
	componentDidMount() {
		Scroll.scroller.scrollTo('calendar-plus-single-event', {
			duration: 500,
			smooth: true,
			offset: -100
		});
	}

	render() {
		let startDate = moment(this.props.event.start).format(formatPHP(calendarPlusi18n.i18n.dateFormat));
		let startTime = moment(this.props.event.start).format(formatPHP(calendarPlusi18n.i18n.timeFormat));
		let endTime = moment(this.props.event.end).format(formatPHP(calendarPlusi18n.i18n.timeFormat));
		let description = {__html: this.props.event.desc};

		let eventDates;
		if (moment(this.props.event.start).format('YYYYMMDD') !== moment(this.props.event.end).format('YYYYMMDD')) {
			// Multiple days spanned
			eventDates = this.props.event.humanDate;
		}
		else {
			eventDates = startDate;
			if (!this.props.event.allDay) {
				eventDates += ' | ' + startTime + ' - ' + endTime
			}
		}

		const calendars = Object.keys(this.props.event.calendars)
			.map((calendar) => {
				return <span key={calendar}><a target="_blank" href={this.props.event.calendars[calendar].url}>{this.props.event.calendars[calendar].name}</a></span>
			})
			.reduce((prev, current) => {
				return [prev, ' | ', current];
			});

		const eventUrl = getEventUrl(this.props.event);

		return <div>
			<Backdrop className={`calendar-plus-single-event-backdrop`} />
			<SingleEventWrapper className={`calendar-plus-single-event-wrap`}>
				<div className={`single_event_inner calendar-plus-single-event`} id="calendar-plus-single-event">
					<div className={`close calendar-plus-close`}>
						<span className={`dashicons dashicons-no-alt`} onClick={this.props.onClose} />
						<button onClick={this.props.onClose} className="show-for-sr">{_e('close')}</button>
					</div>
					<h3>{this.props.event.title}</h3>
					<div className={`event_dates calendar-plus-single-event-dates`}>
						{eventDates}
					</div>
					{
						this.props.event.location ?
							<div className="calendar-plus-single-location">
								{_e('location')} <span dangerouslySetInnerHTML={{__html: this.props.event.location.address}} />
							</div>
							: null
					}

					<div className="calendar-plus-single-event-description" dangerouslySetInnerHTML={description} />
					<div className="calendar-plus-single-event-calendars">
						{_e('addTo')} {calendars}
					</div>
					<a href={eventUrl}>{_e('readMore')}</a>
				</div>
			</SingleEventWrapper>
		</div>
	}
};
