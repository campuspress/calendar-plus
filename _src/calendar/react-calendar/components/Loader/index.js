import React, {Component} from 'react';
import PropTypes from 'prop-types';
import styles from './style.scss';

/**
 * Component to indicate that a subcomponent is being loaded
 *
 * Wrap this into another component and use loading prop to display a loader for that component
 * while you fecth things on server
 */
export default class Loader extends Component {
	render() {
		return <div className={`${styles.loader} calendar-plus-loader`}>
			{this.props.loading ?
				<div className={`${styles.loader_inner} calendar-plus-loader-inner`}>{this.props.label}</div> : null}
			{this.props.children}
		</div>
	}
}
