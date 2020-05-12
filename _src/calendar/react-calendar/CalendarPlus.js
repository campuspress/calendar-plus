import React, {Component} from 'react';
import PropTypes from 'prop-types';
import BigCalendar from 'react-big-calendar';
import Loader from './components/Loader';
import SingleEvent from './components/SingleEvent';
import FilterBar from './components/FilterBar';
import {loadEvents, parseEvents, filterPassedEvents} from './helpers/eventsHelpers';
import {debounce, _e, getMessages, getRealCurrentDate, isMobile} from './helpers/globalHelpers';

/* global: jQuery */

function Event({event}) {
	return (
		<span>
            {!event.allDay ? (<strong className="event-time"> {event.startTime} </strong>) : (null)} {event.title}
        </span>
	)
}

function AgendaEvent({event}) {
	return (
		<span>
            <a target="_blank" href={event.url}>{event.title}</a>
        </span>
	)
}


const components = {
	month: {
		event: Event
	},
	agenda: {
		event: AgendaEvent
	}
};

export default class CalendarPlus extends Component {
	static get defaultProps() {
		return {
			filterable: true
		}
	}

	prevView;

	constructor(props) {
		super(props);

		this.state = {
			view: props.view,
			currentDate: new Date(props.date.year, props.date.month - 1, props.date.day),
			events: [],
			loading: false,
			singleEvent: false,
			category: props.category || '',
			search: ''
		};

		this.prevView = this.state.view;

		this.eventsCache = {};

		this.searchTimer = false;

		// This won't reset the views immediately but with a little delay
		// to avoid problems with unmounted components in React
		this.updateMobileViews = debounce(this.updateMobileViews, 250);
	};

	updateMobileViews = () => {
		if (isMobile()) {
			let params = {views: ['day']};
			if ('day' !== this.state.view) {
				params.view = 'day';
				this.prevView = this.state.view;
			}
			this.setState(params);
		}
		else {
			let params = {views: this.props.views};
			if (this.prevView !== this.state.view) {
				params.view = this.prevView;
			}
			this.setState(params);
		}
	};

	/**
	 * Get current displayed year
	 */
	getYear = () => {
		return this.state.currentDate.getFullYear();
	};

	/**
	 * Get current displayed month
	 */
	getMonth = () => {
		return this.state.currentDate.getMonth();
	};

	/**
	 * Filter events by category
	 *
	 * @param newCategory
	 */
	handleCategoryFilter = (newCategory) => {
		this.setState(
			{category: newCategory},
			this.loadEvents.bind(this, this.getYear(), this.getMonth())
		);
	};

	/**
	 * Filter events by a search string
	 *
	 * @param newSearch
	 */
	handleSearchFilter = (newSearch) => {
		if (this.searchTimer) {
			clearTimeout(this.searchTimer);
		}

		this.searchTimer = setTimeout(() => {
			this.loadEvents(this.getYear(), this.getMonth());
		}, 1000);

		this.setState({search: newSearch});
	};

	/**
	 * Change the current date displayed in the calendar
	 *
	 * @param {Date}   date
	 * @param {string} view
	 * @param {string} action
	 */
	handleNavigate = (date, view, action) => {

		// always display the agenda for the full month
		if ('agenda' === view) {
			date.setDate(1);
		}

		// ensure clicking agenda change button always shifts one month forward or back
		if ('agenda' === this.state.view && ('PREV' === action || 'NEXT' === action) &&
			this.state.currentDate.getMonth() === date.getMonth()) {

			date.setMonth(date.getMonth() + ('PREV' === action ? -1 : 1));
		}

		this.loadEvents(date.getFullYear(), date.getMonth());
		this.setState({
			currentDate: date,
			view: view
		});
	};

	/**
	 * Handle the view change (agenda, month, week, day)
	 *
	 * @param view
	 */
	handleView = (view) => {
		if (view === this.state.view) {
			return;
		}

		this.prevView = this.state.view;
		this.setState({
			view: view
		});
	};

	/**
	 * Triggered when an event is clicked
	 *
	 * @param event
	 */
	handleSelectEvent = (event) => {
		if (this.props.showPopups) {
			this.setState({singleEvent: event});
		}
		else {
			location.href = event.url;
		}
	};

	/**
	 * Load the list of events for a given month and year
	 *
	 * @param year
	 * @param month
	 */
	loadEvents = (year, month) => {
		// JavaScript is a pain in the ass with months
		month++;

		// We're caching so users do not need to reload events when navigating or filtering
		const cacheKey = year + '-' + month + '-' + this.state.category + '-' + this.state.search;
		if (this.eventsCache[cacheKey]) {
			this.setState({events: this.eventsCache[cacheKey]})
		}
		else {
			const filter = {
				category: this.state.category,
				search: this.state.search
			};

			this.setState({loading: true}, () => {
					loadEvents(year, month, filter)
						.then((events) => {
							events = parseEvents(events);
							this.eventsCache[cacheKey] = events;
							this.setState({events: events, loading: false})
						});
				}
			);
		}
	};

	/**
	 * This is a React method triggered just once right before the component is mounted
	 *
	 * In this moment we load the list of events for the current date
	 */
	componentDidMount() {
		this.loadEvents(this.state.currentDate.getFullYear(), this.state.currentDate.getMonth());
		window.addEventListener("resize", this.updateMobileViews);
		this.updateMobileViews();
	}

	componentDidUpdate() {
		// YEAH! jQuery, welcome you bastard!
		// We just need to adjust the calendar height
		const calendars = jQuery('.calendar-plus-calendar-wrap');
		calendars.each(function () {
			let $that = jQuery(this);
			let rbcCalendarHeight = $that.find('.rbc-calendar').first().outerHeight();
			let filterBarHeight = $that.find('.calendar-plus-filter-bar').first().outerHeight();
			$that.parent('.calendar-plus').css('height', filterBarHeight + rbcCalendarHeight);
		});
	}

	render() {
		let events = this.state.loading ? [] : this.state.events;

		if ('agenda' === this.state.view) {
			// filter passed events if we are in Agenda mode (we don't want to display them)
			events = filterPassedEvents(events, getRealCurrentDate());
		}

		return <div className="calendar-plus-big-calendar-wrap">
			<Loader
				loading={this.state.loading}
				label={_e('loading')}
			>
				{this.state.singleEvent ?
					<SingleEvent
						event={this.state.singleEvent}
						onClose={() => {
							this.setState({singleEvent: false})
						}}
					/>
					:
					null
				}

				{this.props.filterable ?
					<FilterBar
						categories={this.props.categories}
						onChangeCategory={this.handleCategoryFilter}
						onChangeSearch={this.handleSearchFilter}
						messages={getMessages()}
						currentCategory={this.state.category}
						currentSearch={this.state.search}
					/>
					: null
				}

				<BigCalendar
					localizer={this.props.localizer}
					className="calendar-plus-big-calendar"
					events={events}
					defaultDate={this.state.currentDate}
					currentDate={this.state.currentDate}
					view={this.state.view}
					onView={this.handleView}
					onNavigate={this.handleNavigate}
					onSelectEvent={this.handleSelectEvent}
					messages={getMessages()}
					rtl={this.props.rtl}
					views={this.props.views}
					formats={this.props.formats}
					components={components}
					popup={true}
				/>
			</Loader>
		</div>
	}
}


CalendarPlus.propTypes = {
	rtl: PropTypes.bool.isRequired,
	showPopups: PropTypes.bool.isRequired,
	date: PropTypes.object.isRequired,
	categories: PropTypes.object.isRequired,
	filterable: PropTypes.bool
};


