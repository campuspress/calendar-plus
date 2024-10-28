import React, {Component} from 'react';
import { StyledLoader } from './styles';

/**
 * Component to indicate that a subcomponent is being loaded
 *
 * Wrap this into another component and use loading prop to display a loader for that component
 * while you fecth things on server
 */
export default class Loader extends Component {
	render() {
		return <StyledLoader className={`calendar-plus-loader`}>
			{this.props.loading ?
				<div className={`loader_inner calendar-plus-loader-inner`}>{this.props.label}</div> : null}
			{this.props.children}
		</StyledLoader>
	}
}
