import styled from 'styled-components';

const StyledLoader = styled.div`
	height:100%;
	position: relative;

	.loader_inner {
		position:absolute;
		top:0;
		padding-top:80px;
		height:100%;
		background-color: rgba(255, 255, 255, 0.7);
		width:100%;
		text-align:center;
		color:black;
		z-index:999;
	}
`;

export { StyledLoader };