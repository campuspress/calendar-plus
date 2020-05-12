import React, {Component} from 'react';
import PropTypes from 'prop-types';
import {_e} from '../../helpers/globalHelpers';

export default class FilterBar extends Component {
	handleInputChange = (e) => {
		const fn = 'onChange' + e.target.name;
		this.props[fn](e.target.value);
	};

	render() {
		const categoriesOptions = Object.keys(this.props.categories).map((key) => {
			const category = this.props.categories[key];
			// dangerouslySetInnerHTML will convert HTML entities
			return <option key={category.id} value={category.id} dangerouslySetInnerHTML={{__html: category.name}} />
		});

		return <div className="calendar-plus-filter-bar">
			{categoriesOptions.length ?

				<p>
					<select value={this.props.currentCategory} name="Category" onChange={this.handleInputChange} id="calendar-plus-category">
						<option value="">{_e('selectCategory')}</option>
						{categoriesOptions}
					</select>
				</p>

				: null
			}
			<p>
				<input type="text" value={this.props.currentSearch} name="Search" onChange={this.handleInputChange} id="calendar-plus-search" placeholder={_e('searchPlaceholder')} />
			</p>
		</div>
	}
}

FilterBar.propTypes = {
	categories: PropTypes.object.isRequired,
	onChangeCategory: PropTypes.func.isRequired,
	onChangeSearch: PropTypes.func.isRequired,
	currentCategory: PropTypes.any,
	currentSearch: PropTypes.string
};
